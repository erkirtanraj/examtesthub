<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExamManagementController extends Controller
{
    private function audit(Request $request, string $action, Exam $exam, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => ['exam_id' => $exam->id, ...$metadata],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Exams/Index', [
            'exams' => Exam::withCount('questions')->latest('id')->paginate(20),
            'questions' => Question::where('status', 'published')->with('subject:id,name')->orderBy('id', 'desc')->limit(500)->get(['id', 'subject_id', 'prompt', 'type', 'marks', 'negative_marks']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'shuffle_questions' => ['required', 'boolean'],
            'shuffle_options' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'question_ids' => ['required', 'array', 'min:1', 'max:500'],
            'question_ids.*' => ['integer', 'distinct', 'exists:questions,id'],
        ]);
        $publishedCount = Question::whereIn('id', $data['question_ids'])->where('status', 'published')->count();
        if ($publishedCount !== count($data['question_ids'])) {
            abort(422, 'Only published questions can be added to an exam.');
        }
        if ($data['status'] === 'scheduled' && (! $data['starts_at'] || ! $data['ends_at'])) {
            abort(422, 'Scheduled exams require both start and end times.');
        }

        return $data;
    }

    private function syncQuestions(Exam $exam, array $questionIds): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $index => $id) {
            $question = Question::findOrFail($id);
            $sync[$id] = [
                'position' => $index + 1,
                'marks' => $question->marks,
                'negative_marks' => $question->negative_marks,
            ];
        }
        $exam->questions()->sync($sync);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $questionIds = $data['question_ids'];
        unset($data['question_ids']);
        $base = Str::slug($data['title']);
        $data['slug'] = $base.'-'.Str::lower(Str::random(6));
        $data['created_by'] = $request->user()->id;
        $exam = Exam::create($data);
        $this->syncQuestions($exam, $questionIds);
        $this->audit($request, 'exam.created', $exam, ['question_count' => count($questionIds)]);

        return back()->with('success', 'Exam created.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->status === 'archived', 422, 'Archived exams cannot be edited.');
        $data = $this->validated($request);
        $questionIds = $data['question_ids'];
        unset($data['question_ids'], $data['status']);
        $exam->update($data);
        $this->syncQuestions($exam, $questionIds);
        $this->audit($request, 'exam.updated', $exam, ['question_count' => count($questionIds)]);

        return back()->with('success', 'Exam updated.');
    }

    public function updateStatus(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])]]);
        if (in_array($data['status'], ['scheduled', 'published'], true)) {
            abort_if($exam->questions()->count() < 1, 422, 'Add at least one published question before scheduling or publishing.');
            if ($data['status'] === 'scheduled') {
                abort_if(! $exam->starts_at || ! $exam->ends_at || $exam->ends_at->lessThanOrEqualTo($exam->starts_at), 422, 'A scheduled exam needs a valid start and end time.');
            }
        }
        $exam->update(['status' => $data['status']]);
        $this->audit($request, 'exam.status_changed', $exam, ['status' => $data['status']]);

        return back()->with('success', 'Exam status updated.');
    }
}
