<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseLesson;
use App\Models\CourseLessonResource;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseManagementController extends Controller
{
    private function audit(Request $request, string $action, Course $course, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => ['course_id' => $course->id, ...$metadata],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Courses/Index', [
            'courses' => Course::with('creator:id,name')
                ->withCount(['sections', 'enrollments'])
                ->latest('id')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCourse($request);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $data['created_by'] = $request->user()->id;
        $course = Course::create($data);
        $this->audit($request, 'course.created', $course);
        return back()->with('success', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        abort_if($course->status === 'archived', 422, 'Archived courses cannot be edited.');
        $data = $this->validatedCourse($request);
        unset($data['status']);
        $course->update($data);
        $this->audit($request, 'course.updated', $course);
        return back()->with('success', 'Course updated.');
    }

    private function validatedCourse(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'title_hi' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:30000'],
            'description_hi' => ['nullable', 'string', 'max:30000'],
            'category' => ['nullable', 'string', 'max:120'],
            'access_type' => ['required', Rule::in(['free', 'paid'])],
            'price' => [$request->input('access_type') === 'paid' ? 'required' : 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'in:INR'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);
        if ($data['access_type'] === 'free') $data['price'] = 0;
        if ($data['status'] === 'published') {
            abort_if($request->route('course') && $request->route('course')->sections()->whereHas('chapters.lessons', fn ($q) => $q->where('status', 'published'))->doesntExist(), 422, 'A course needs at least one published lesson before publishing.');
            $data['published_at'] = now();
        }
        return $data;
    }

    public function updateStatus(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
        if ($data['status'] === 'published') {
            abort_if(! $course->sections()->whereHas('chapters.lessons', fn ($q) => $q->where('status', 'published'))->exists(), 422, 'A course needs at least one published lesson before publishing.');
            $data['published_at'] = $course->published_at ?? now();
        }
        $course->update($data);
        $this->audit($request, 'course.status_changed', $course, ['status' => $data['status']]);
        return back()->with('success', 'Course status updated.');
    }

    public function builder(Course $course): Response
    {
        $course->load(['sections.chapters.lessons.resources']);
        return Inertia::render('Admin/Courses/Builder', ['course' => $course]);
    }

    public function storeSection(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'], 'description' => ['nullable','string','max:5000'], 'description_hi' => ['nullable','string','max:5000']]);
        $data['position'] = (int) $course->sections()->max('position') + 1;
        $course->sections()->create($data);
        $this->audit($request, 'course.section_created', $course);
        return back()->with('success', 'Section added.');
    }

    public function updateSection(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);
        $section->update($request->validate(['title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'], 'description' => ['nullable','string','max:5000'], 'description_hi' => ['nullable','string','max:5000'], 'position' => ['required','integer','min:1']]));
        return back()->with('success', 'Section updated.');
    }

    public function storeChapter(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);
        $data = $request->validate(['title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180']]);
        $data['position'] = (int) $section->chapters()->max('position') + 1;
        $section->chapters()->create($data);
        return back()->with('success', 'Chapter added.');
    }

    public function updateChapter(Request $request, Course $course, CourseChapter $chapter): RedirectResponse
    {
        abort_unless($chapter->section()->where('course_id', $course->id)->exists(), 404);
        $chapter->update($request->validate(['title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'], 'position' => ['required','integer','min:1']]));
        return back()->with('success', 'Chapter updated.');
    }

    public function storeLesson(Request $request, Course $course, CourseChapter $chapter): RedirectResponse
    {
        abort_unless($chapter->section()->where('course_id', $course->id)->exists(), 404);
        $data = $request->validate([
            'title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'],
            'description' => ['nullable','string','max:10000'], 'description_hi' => ['nullable','string','max:10000'],
            'type' => ['required', Rule::in(['video','text'])], 'video_url' => ['nullable','url','max:2048'],
            'content' => ['nullable','string','max:100000'], 'is_preview' => ['boolean'],
            'duration_minutes' => ['nullable','integer','min:0','max:65535'],
            'status' => ['required', Rule::in(['draft','published'])],
        ]);
        if ($data['type'] === 'video') abort_if(blank($data['video_url'] ?? null), 422, 'Video URL is required for video lessons.');
        if ($data['type'] === 'text') abort_if(blank($data['content'] ?? null), 422, 'Lesson content is required for text lessons.');
        $data['position'] = (int) $chapter->lessons()->max('position') + 1;
        $chapter->lessons()->create($data);
        return back()->with('success', 'Lesson added.');
    }

    public function updateLesson(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($lesson->chapter()->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->exists(), 404);
        $data = $request->validate([
            'title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'],
            'description' => ['nullable','string','max:10000'], 'description_hi' => ['nullable','string','max:10000'],
            'type' => ['required', Rule::in(['video','text'])], 'video_url' => ['nullable','url','max:2048'],
            'content' => ['nullable','string','max:100000'], 'is_preview' => ['boolean'],
            'duration_minutes' => ['nullable','integer','min:0','max:65535'],
            'status' => ['required', Rule::in(['draft','published'])], 'position' => ['required','integer','min:1'],
        ]);
        if ($data['type'] === 'video') abort_if(blank($data['video_url'] ?? null), 422, 'Video URL is required for video lessons.');
        if ($data['type'] === 'text') abort_if(blank($data['content'] ?? null), 422, 'Lesson content is required for text lessons.');
        $lesson->update($data);
        return back()->with('success', 'Lesson updated.');
    }

    public function storeResource(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($lesson->chapter()->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->exists(), 404);
        $data = $request->validate(['title' => ['required','string','max:180'], 'title_hi' => ['nullable','string','max:180'], 'url' => ['required','url','max:2048']]);
        $lesson->resources()->create([...$data, 'type' => 'link', 'position' => (int) $lesson->resources()->max('position') + 1]);
        return back()->with('success', 'Resource link added.');
    }
}
