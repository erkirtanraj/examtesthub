<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:30,90,365'],
        ]);
        $days = (int) ($filters['days'] ?? 90);
        $userId = $request->user()->id;
        $from = now()->subDays($days - 1)->startOfDay();

        $submitted = ExamAttempt::query()
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $from);

        $summary = (clone $submitted)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as average_percentage')
            ->selectRaw('MAX(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as best_percentage')
            ->selectRaw('SUM(correct_count) as correct')
            ->selectRaw('SUM(incorrect_count) as incorrect')
            ->selectRaw('SUM(unanswered_count) as unanswered')
            ->first();

        $subjectPerformance = DB::table('exam_attempt_answers as answers')
            ->join('exam_attempts as attempts', 'attempts.id', '=', 'answers.exam_attempt_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'submitted')
            ->whereNotNull('attempts.submitted_at')
            ->where('attempts.submitted_at', '>=', $from)
            ->selectRaw("COALESCE(subjects.name, 'Uncategorized') as subject")
            ->selectRaw('COUNT(*) as responses')
            ->selectRaw('SUM(CASE WHEN answers.answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 0 THEN 1 ELSE 0 END) as incorrect')
            ->selectRaw('SUM(CASE WHEN answers.answered_at IS NULL THEN 1 ELSE 0 END) as unanswered')
            ->groupBy('subjects.name')
            ->orderBy('subject')
            ->get()
            ->map(fn ($row) => [
                'subject' => $row->subject,
                'responses' => (int) $row->responses,
                'answered' => (int) $row->answered,
                'correct' => (int) $row->correct,
                'incorrect' => (int) $row->incorrect,
                'unanswered' => (int) $row->unanswered,
                'accuracy' => (int) $row->answered > 0
                    ? round(((int) $row->correct / (int) $row->answered) * 100, 1)
                    : null,
            ]);

        $history = (clone $submitted)
            ->with('exam:id,title')
            ->latest('submitted_at')
            ->limit(30)
            ->get(['id', 'exam_id', 'score', 'total_marks', 'correct_count', 'incorrect_count', 'unanswered_count', 'submitted_at'])
            ->map(function (ExamAttempt $attempt) use ($userId) {
                $percentage = (float) $attempt->total_marks > 0
                    ? round(((float) $attempt->score / (float) $attempt->total_marks) * 100, 1)
                    : null;
                $rank = null;
                $cohortSize = null;
                if ($percentage !== null) {
                    $bestByStudent = DB::table('exam_attempts')
                        ->where('exam_id', $attempt->exam_id)
                        ->where('status', 'submitted')
                        ->where('total_marks', '>', 0)
                        ->select('user_id')
                        ->selectRaw('MAX(score * 100.0 / total_marks) as best_percentage')
                        ->groupBy('user_id');
                    $cohortSize = (clone $bestByStudent)->count();
                    $betterCount = DB::query()->fromSub(clone $bestByStudent, 'cohort')
                        ->where('best_percentage', '>', $percentage)
                        ->count();
                    $rank = $betterCount + 1;
                }

                return [
                    'id' => $attempt->id,
                    'exam' => $attempt->exam?->title ?? 'Deleted exam',
                    'score' => (float) $attempt->score,
                    'total_marks' => (float) $attempt->total_marks,
                    'percentage' => $percentage,
                    'correct' => (int) $attempt->correct_count,
                    'incorrect' => (int) $attempt->incorrect_count,
                    'unanswered' => (int) $attempt->unanswered_count,
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'rank' => $rank,
                    'cohort_size' => $cohortSize,
                    'percentile' => $cohortSize
                        ? round((($cohortSize - $betterCount) / $cohortSize) * 100, 1)
                        : null,
                ];
            });

        return Inertia::render('Student/Reports/Index', [
            'student' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'generated_at' => now()->toIso8601String(),
            ],
            'filters' => ['days' => $days],
            'summary' => [
                'attempts' => (int) ($summary->attempts ?? 0),
                'average_percentage' => round((float) ($summary->average_percentage ?? 0), 1),
                'best_percentage' => round((float) ($summary->best_percentage ?? 0), 1),
                'correct' => (int) ($summary->correct ?? 0),
                'incorrect' => (int) ($summary->incorrect ?? 0),
                'unanswered' => (int) ($summary->unanswered ?? 0),
            ],
            'subject_performance' => $subjectPerformance,
            'exam_history' => $history,
        ]);
    }
}
