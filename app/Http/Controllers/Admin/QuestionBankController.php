<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankController extends Controller
{
    private function audit(Request $request, string $action, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'language' => ['nullable', Rule::in(['en', 'hi'])],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $query = Question::with(['subject:id,name', 'topic:id,name'])->latest('id');
        $query->when($filters['search'] ?? null, fn ($q, $term) => $q->where('prompt', 'like', "%{$term}%"));
        foreach (['subject_id', 'topic_id', 'difficulty', 'status', 'language'] as $key) {
            $query->when($filters[$key] ?? null, fn ($q, $value) => $q->where($key, $value));
        }

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $query->paginate($filters['per_page'] ?? 25)->withQueryString(),
            'subjects' => Subject::with('topics:id,subject_id,name')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    private function validatedQuestion(Request $request): array
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', Rule::exists('topics', 'id')->where('subject_id', $request->input('subject_id'))],
            'type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'true_false', 'short_answer'])],
            'prompt' => ['required', 'string', 'max:20000'],
            'options' => ['nullable', 'array', 'max:10'],
            'options.*' => ['nullable', 'string', 'max:1000'],
            'correct_answer' => ['required', 'array', 'min:1', 'max:10'],
            'correct_answer.*' => ['required', 'string', 'max:1000'],
            'explanation' => ['nullable', 'string', 'max:20000'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'language' => ['required', Rule::in(['en', 'hi'])],
            'marks' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'negative_marks' => ['required', 'numeric', 'min:0', 'max:1000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (in_array($data['type'], ['single_choice', 'multiple_choice'], true)) {
            $options = array_filter($data['options'] ?? [], fn ($value) => filled($value));
            if (count($options) < 2 || array_diff($data['correct_answer'], array_keys($options)) !== []) {
                abort(422, 'Choice questions need at least two options and valid answer keys.');
            }
            $data['options'] = $options;
        } elseif ($data['type'] === 'true_false') {
            $data['options'] = ['true' => 'True', 'false' => 'False'];
            if (array_diff($data['correct_answer'], ['true', 'false']) !== []) {
                abort(422, 'True/false answer must be true or false.');
            }
        } else {
            $data['options'] = null;
        }

        if ($data['type'] === 'single_choice' && count($data['correct_answer']) !== 1) {
            abort(422, 'Single-choice questions must have exactly one correct answer.');
        }

        return $data;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedQuestion($request);
        $data['created_by'] = $request->user()->id;
        $question = Question::create($data);
        $this->audit($request, 'question.created', ['question_id' => $question->id, 'status' => $question->status]);

        return back()->with('success', 'Question created.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        abort_if($question->exams()->whereIn('status', ['published', 'scheduled'])->exists(), 422, 'Questions in a live or scheduled exam cannot be edited.');
        $data = $this->validatedQuestion($request);
        $question->update($data);
        $this->audit($request, 'question.updated', ['question_id' => $question->id, 'status' => $question->status]);

        return back()->with('success', 'Question updated.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        abort_if($question->exams()->exists(), 422, 'Remove this question from its exams before deleting it.');
        $id = $question->id;
        $question->delete();
        $this->audit($request, 'question.deleted', ['question_id' => $id]);

        return back()->with('success', 'Question deleted.');
    }

    public function subjects(): Response
    {
        return Inertia::render('Admin/Questions/Subjects', [
            'subjects' => Subject::withCount(['topics', 'questions'])->with('topics')->orderBy('name')->get(),
        ]);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $base = Str::slug($data['name']);
        $slug = $base;
        $suffix = 2;
        while (Subject::where('slug', $slug)->exists()) $slug = $base.'-'.$suffix++;
        $subject = Subject::create([...$data, 'slug' => $slug]);
        $this->audit($request, 'subject.created', ['subject_id' => $subject->id]);

        return back()->with('success', 'Subject created.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $subject->update($data);
        $this->audit($request, 'subject.updated', ['subject_id' => $subject->id]);

        return back()->with('success', 'Subject updated.');
    }

    public function storeTopic(Request $request, Subject $subject): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $base = Str::slug($data['name']);
        $slug = $base;
        $suffix = 2;
        while ($subject->topics()->where('slug', $slug)->exists()) $slug = $base.'-'.$suffix++;
        $topic = $subject->topics()->create([...$data, 'slug' => $slug]);
        $this->audit($request, 'topic.created', ['topic_id' => $topic->id, 'subject_id' => $subject->id]);

        return back()->with('success', 'Topic created.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'examtesthub-questions-'.now()->format('Ymd-His').'.csv';
        $query = Question::with('subject', 'topic')->orderBy('id');
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'subject', 'topic', 'type', 'prompt', 'options_json', 'correct_answer_json', 'explanation', 'difficulty', 'language', 'marks', 'negative_marks', 'status']);
            $query->chunk(500, function ($questions) use ($out) {
                foreach ($questions as $q) {
                    fputcsv($out, [$q->id, $q->subject?->name, $q->topic?->name, $q->type, $q->prompt, json_encode($q->options), json_encode($q->correct_answer), $q->explanation, $q->difficulty, $q->language, $q->marks, $q->negative_marks, $q->status]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        abort_unless($handle, 422, 'Unable to read uploaded CSV.');
        $header = fgetcsv($handle);
        $expected = ['subject', 'topic', 'type', 'prompt', 'options_json', 'correct_answer_json', 'explanation', 'difficulty', 'language', 'marks', 'negative_marks'];
        abort_unless($header && array_map('strtolower', $header) === $expected, 422, 'CSV headers do not match the documented question import format.');

        $created = 0;
        $rejected = 0;
        $line = 1;
        DB::transaction(function () use ($handle, &$created, &$rejected, &$line, $request) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) !== 11) { $rejected++; continue; }
                [$subjectName, $topicName, $type, $prompt, $optionsJson, $answersJson, $explanation, $difficulty, $language, $marks, $negative] = $row;
                $subject = Subject::where('name', trim($subjectName))->first();
                $topic = $topicName !== '' ? $subject?->topics()->where('name', trim($topicName))->first() : null;
                $options = json_decode($optionsJson, true);
                $answers = json_decode($answersJson, true);
                $validType = in_array($type, ['single_choice', 'multiple_choice', 'true_false', 'short_answer'], true);
                $validAnswers = is_array($answers) && count($answers) > 0;
                if (in_array($type, ['single_choice', 'multiple_choice'], true)) {
                    $validAnswers = $validAnswers && is_array($options) && count($options) >= 2
                        && array_diff($answers, array_keys($options)) === []
                        && ($type !== 'single_choice' || count($answers) === 1);
                } elseif ($type === 'true_false') {
                    $validAnswers = $validAnswers && count($answers) === 1 && in_array($answers[0], ['true', 'false'], true);
                    $options = ['true' => 'True', 'false' => 'False'];
                }
                if (! $subject || ($topicName !== '' && ! $topic) || ! $validType || ! $validAnswers
                    || ! in_array($difficulty, ['easy', 'medium', 'hard'], true) || ! in_array($language, ['en', 'hi'], true)
                    || ! is_numeric($marks) || (float) $marks <= 0 || ! is_numeric($negative) || (float) $negative < 0
                    || trim($prompt) === '') {
                    $rejected++;
                    continue;
                }
                Question::create([
                    'subject_id' => $subject->id, 'topic_id' => $topic?->id, 'created_by' => $request->user()->id,
                    'type' => $type, 'prompt' => $prompt, 'options' => $options, 'correct_answer' => $answers,
                    'explanation' => $explanation ?: null, 'difficulty' => $difficulty, 'language' => $language,
                    'marks' => $marks, 'negative_marks' => $negative, 'status' => 'draft',
                ]);
                $created++;
            }
        });
        fclose($handle);
        $this->audit($request, 'questions.imported', ['created' => $created, 'rejected' => $rejected]);

        return back()->with('success', "{$created} questions imported as drafts; {$rejected} rows skipped.");
    }
}
