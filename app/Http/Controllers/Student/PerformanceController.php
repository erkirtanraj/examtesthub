<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\ExamAttempt;
use App\Models\TestSeriesEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $submitted = ExamAttempt::query()
            ->where('user_id', $userId)
            ->where('status', 'submitted');

        $summary = (clone $submitted)
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN (score * 100.0 / total_marks) ELSE NULL END) as average_percentage')
            ->selectRaw('MAX(CASE WHEN total_marks > 0 THEN (score * 100.0 / total_marks) ELSE NULL END) as best_percentage')
            ->first();

        $recentAttempts = (clone $submitted)
            ->with('exam:id,title')
            ->latest('submitted_at')
            ->limit(12)
            ->get(['id', 'exam_id', 'score', 'total_marks', 'correct_count', 'incorrect_count', 'unanswered_count', 'submitted_at'])
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'exam' => $attempt->exam?->title ?? 'Deleted exam',
                'score' => (float) $attempt->score,
                'total_marks' => (float) $attempt->total_marks,
                'percentage' => $attempt->total_marks > 0 ? round(((float) $attempt->score / (float) $attempt->total_marks) * 100, 1) : null,
                'correct' => (int) $attempt->correct_count,
                'incorrect' => (int) $attempt->incorrect_count,
                'unanswered' => (int) $attempt->unanswered_count,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ]);

        $courseEnrollments = CourseEnrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])->count();

        $seriesEnrollments = TestSeriesEnrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])->count();

        return Inertia::render('Student/Performance/Index', [
            'summary' => [
                'attempts' => (int) ($summary->attempts_count ?? 0),
                'average_percentage' => round((float) ($summary->average_percentage ?? 0), 1),
                'best_percentage' => round((float) ($summary->best_percentage ?? 0), 1),
                'course_enrollments' => $courseEnrollments,
                'series_enrollments' => $seriesEnrollments,
            ],
            'recent_attempts' => $recentAttempts,
        ]);
    }
}
