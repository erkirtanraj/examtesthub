<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamAnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $days = (int) ($filters['days'] ?? 30);
        $examId = $filters['exam_id'] ?? null;
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $attempts = ExamAttempt::query()
            ->where('exam_attempts.status', 'submitted')
            ->whereBetween('exam_attempts.submitted_at', [$from, $to])
            ->when($examId, fn ($query) => $query->where('exam_id', $examId));

        $summaryRow = (clone $attempts)
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('COUNT(DISTINCT user_id) as students_count')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as average_percentage')
            ->selectRaw('SUM(correct_count) as correct_count')
            ->selectRaw('SUM(incorrect_count) as incorrect_count')
            ->selectRaw('SUM(unanswered_count) as unanswered_count')
            ->first();

        $questionRows = DB::table('exam_attempt_answers as answers')
            ->join('exam_attempts as attempts', 'attempts.id', '=', 'answers.exam_attempt_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->where('attempts.status', 'submitted')
            ->whereBetween('attempts.submitted_at', [$from, $to])
            ->when($examId, fn ($query) => $query->where('attempts.exam_id', $examId))
            ->select(
                'questions.id',
                'questions.prompt',
                'questions.difficulty',
                'questions.type',
                'subjects.name as subject'
            )
            ->selectRaw('COUNT(*) as response_count')
            ->selectRaw('SUM(CASE WHEN answers.answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered_count')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count')
            ->selectRaw('SUM(CASE WHEN answers.answered_at IS NULL THEN 1 ELSE 0 END) as unanswered_count')
            ->groupBy('questions.id', 'questions.prompt', 'questions.difficulty', 'questions.type', 'subjects.name')
            ->orderByRaw('CASE WHEN SUM(CASE WHEN answers.answered_at IS NOT NULL THEN 1 ELSE 0 END) > 0 THEN SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / SUM(CASE WHEN answers.answered_at IS NOT NULL THEN 1 ELSE 0 END) ELSE 0 END ASC')
            ->orderByDesc('response_count')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'prompt' => $row->prompt,
                'subject' => $row->subject ?? '—',
                'difficulty' => $row->difficulty,
                'type' => $row->type,
                'responses' => (int) $row->response_count,
                'answered' => (int) $row->answered_count,
                'correct' => (int) $row->correct_count,
                'incorrect' => (int) $row->incorrect_count,
                'unanswered' => (int) $row->unanswered_count,
                'accuracy' => (int) $row->answered_count > 0
                    ? round(((int) $row->correct_count / (int) $row->answered_count) * 100, 1)
                    : null,
            ]);

        $examPerformance = (clone $attempts)
            ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
            ->select('exams.id', 'exams.title')
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('COUNT(DISTINCT exam_attempts.user_id) as students_count')
            ->selectRaw('AVG(CASE WHEN exam_attempts.total_marks > 0 THEN exam_attempts.score * 100.0 / exam_attempts.total_marks ELSE NULL END) as average_percentage')
            ->groupBy('exams.id', 'exams.title')
            ->orderByDesc('attempts_count')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => $row->title,
                'attempts' => (int) $row->attempts_count,
                'students' => (int) $row->students_count,
                'average_percentage' => round((float) ($row->average_percentage ?? 0), 1),
            ]);

        $recentAttempts = (clone $attempts)
            ->with(['user:id,name,email', 'exam:id,title'])
            ->latest('submitted_at')
            ->limit(20)
            ->get(['id', 'user_id', 'exam_id', 'score', 'total_marks', 'correct_count', 'incorrect_count', 'unanswered_count', 'submitted_at'])
            ->map(fn (ExamAttempt $attempt) => [
                'id' => $attempt->id,
                'student' => $attempt->user?->name ?? 'Deleted user',
                'email' => $attempt->user?->email,
                'exam' => $attempt->exam?->title ?? 'Deleted exam',
                'score' => (float) $attempt->score,
                'total_marks' => (float) $attempt->total_marks,
                'percentage' => $attempt->total_marks > 0
                    ? round(((float) $attempt->score / (float) $attempt->total_marks) * 100, 1)
                    : null,
                'correct' => (int) $attempt->correct_count,
                'incorrect' => (int) $attempt->incorrect_count,
                'unanswered' => (int) $attempt->unanswered_count,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/ExamAnalytics/Index', [
            'filters' => ['days' => $days, 'exam_id' => $examId ? (int) $examId : null],
            'exams' => Exam::query()->orderBy('title')->get(['id', 'title']),
            'summary' => [
                'attempts' => (int) ($summaryRow->attempts_count ?? 0),
                'students' => (int) ($summaryRow->students_count ?? 0),
                'average_percentage' => round((float) ($summaryRow->average_percentage ?? 0), 1),
                'correct' => (int) ($summaryRow->correct_count ?? 0),
                'incorrect' => (int) ($summaryRow->incorrect_count ?? 0),
                'unanswered' => (int) ($summaryRow->unanswered_count ?? 0),
            ],
            'exam_performance' => $examPerformance,
            'question_performance' => $questionRows,
            'recent_attempts' => $recentAttempts,
        ]);
    }
}
