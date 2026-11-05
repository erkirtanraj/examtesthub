<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudyMaterialController extends Controller
{
    private function audit(Request $request, string $action, StudyMaterial $material): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => [
                'study_material_id' => $material->id,
                'subject_id' => $material->subject_id,
                'course_id' => $material->course_id,
                'status' => $material->status,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'per_page' => ['nullable', 'integer', 'in:10,15,30,50'],
        ]);

        $materials = StudyMaterial::with([
            'subject:id,name',
            'course:id,title,title_hi',
            'creator:id,name',
        ])
            ->when($filters['search'] ?? null, function ($query, $term) {
                $query->where(function ($search) use ($term) {
                    $search->where('title', 'like', "%{$term}%")
                        ->orWhere('title_hi', 'like', "%{$term}%")
                        ->orWhere('original_name', 'like', "%{$term}%");
                });
            })
            ->when($filters['subject_id'] ?? null, fn ($query, $value) => $query->where('subject_id', $value))
            ->when($filters['course_id'] ?? null, fn ($query, $value) => $query->where('course_id', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return Inertia::render('Admin/Materials/Index', [
            'materials' => $materials,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'courses' => Course::where('status', '!=', 'archived')->orderBy('title')->get(['id', 'title', 'title_hi']),
            'filters' => $filters,
        ]);
    }

    private function validatedMaterial(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'title_hi' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_hi' => ['nullable', 'string', 'max:10000'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'language' => ['required', Rule::in(['en', 'hi', 'both'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:51200'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedMaterial($request, true);
        $file = $data['file'];
        unset($data['file']);

        $data['file_path'] = $file->store('study-materials', 'local');
        $data['original_name'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getMimeType();
        $data['file_size'] = $file->getSize();
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $material = StudyMaterial::create($data);
        $this->audit($request, 'study_material.created', $material);

        return back()->with('success', 'Study material uploaded.');
    }

    public function update(Request $request, StudyMaterial $studyMaterial): RedirectResponse
    {
        $data = $this->validatedMaterial($request, false);
        $file = $data['file'] ?? null;
        unset($data['file']);

        $oldPath = $studyMaterial->file_path;
        if ($file) {
            $data['file_path'] = $file->store('study-materials', 'local');
            $data['original_name'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
        }

        $data['published_at'] = $data['status'] === 'published'
            ? ($studyMaterial->published_at ?? now())
            : null;

        $studyMaterial->update($data);
        if ($file && $oldPath !== $studyMaterial->file_path) {
            Storage::disk('local')->delete($oldPath);
        }
        $this->audit($request, 'study_material.updated', $studyMaterial);

        return back()->with('success', 'Study material updated.');
    }

    public function updateStatus(Request $request, StudyMaterial $studyMaterial): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
        $data['published_at'] = $data['status'] === 'published'
            ? ($studyMaterial->published_at ?? now())
            : null;

        $studyMaterial->update($data);
        $this->audit($request, 'study_material.status_changed', $studyMaterial);

        return back()->with('success', 'Study material status updated.');
    }

    public function destroy(Request $request, StudyMaterial $studyMaterial): RedirectResponse
    {
        $filePath = $studyMaterial->file_path;
        $this->audit($request, 'study_material.deleted', $studyMaterial);
        $studyMaterial->delete();
        Storage::disk('local')->delete($filePath);

        return back()->with('success', 'Study material deleted.');
    }
}
