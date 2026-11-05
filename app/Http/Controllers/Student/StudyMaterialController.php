<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudyMaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'language' => ['nullable', Rule::in(['en', 'hi', 'both'])],
        ]);

        $userId = $request->user()->id;
        $materials = StudyMaterial::where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('course_id')
                    ->orWhereHas('course', function ($course) {
                        $course->where('status', 'published')
                            ->where(fn ($schedule) => $schedule->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                            ->where(fn ($schedule) => $schedule->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                    });
            })
            ->with([
                'subject:id,name',
                'course:id,title,title_hi,slug,access_type',
                'course.enrollments' => fn ($query) => $query
                    ->where('user_id', $userId)
                    ->whereIn('status', ['active', 'completed']),
            ])
            ->when($filters['search'] ?? null, function ($query, $term) {
                $query->where(function ($search) use ($term) {
                    $search->where('title', 'like', "%{$term}%")
                        ->orWhere('title_hi', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('description_hi', 'like', "%{$term}%");
                });
            })
            ->when($filters['subject_id'] ?? null, fn ($query, $value) => $query->where('subject_id', $value))
            ->when($filters['language'] ?? null, fn ($query, $value) => $value === 'both'
                ? $query->where('language', 'both')
                : $query->whereIn('language', [$value, 'both']))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(function (StudyMaterial $material) {
                $course = $material->course;
                $hasCourseAccess = $course && $course->enrollments->isNotEmpty();

                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'title_hi' => $material->title_hi,
                    'description' => $material->description,
                    'description_hi' => $material->description_hi,
                    'language' => $material->language,
                    'original_name' => $material->original_name,
                    'file_size' => $material->file_size,
                    'published_at' => $material->published_at,
                    'subject' => $material->subject?->name,
                    'course' => $course ? [
                        'title' => $course->title,
                        'title_hi' => $course->title_hi,
                        'slug' => $course->slug,
                        'access_type' => $course->access_type,
                    ] : null,
                    'can_download' => ! $material->course_id || (bool) $hasCourseAccess,
                ];
            });

        return Inertia::render('Student/Materials/Index', [
            'materials' => $materials,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function download(Request $request, StudyMaterial $studyMaterial): StreamedResponse
    {
        abort_unless($studyMaterial->status === 'published', 404);

        if ($studyMaterial->course_id) {
            abort_unless($studyMaterial->course && $studyMaterial->course->isAvailable(), 404);
            $hasAccess = CourseEnrollment::where('course_id', $studyMaterial->course_id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
            abort_unless($hasAccess, 403);
        }

        abort_unless($studyMaterial->file_path && Storage::disk('local')->exists($studyMaterial->file_path), 404);

        return Storage::disk('local')->download(
            $studyMaterial->file_path,
            $studyMaterial->original_name ?: basename($studyMaterial->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }
}
