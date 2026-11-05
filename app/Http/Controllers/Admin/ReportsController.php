<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\CourseEnrollment;
use App\\Models\\ExamAttempt;
use App\\Models\\Payment;
use App\\Models\\TestSeriesEnrollment;
use App\\Models\\User;
use Carbon\\Carbon;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Inertia\\Inertia;
use Inertia\\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90,365'],
        ]);

        $days = (int) ($filters['days'] ?? 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $submittedAttempts = ExamAttempt::query()
            ->where('status', 'submitted')
            ->whereBetween('submitted_at', [$from, $to]);

        $attemptSummary = (clone $submittedAttempts)
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN (score / total_marks) * 100 ELSE NULL END) as average_percentage')
            ->first();

        $capturedPayments = Payment::query()
            ->where('status', 'captured')
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [$from, $to]);

        $studentsTotal = User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))->count();
        $newStudents = User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $courseEnrollments = CourseEnrollment::whereIn('status', ['active', 'completed'])
            ->whereBetween('enrolled_at', [$from, $to])->count();
        $seriesEnrollments = TestSeriesEnrollment::whereIn('status', ['active', 'completed'])
            ->whereBetween('enrolled_at', [$from, $to])->count();

        $attemptTrendRows = (clone $submittedAttempts)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as attempts, AVG(CASE WHEN total_marks > 0 THEN (score / total_marks) * 100 ELSE NULL END) as average_percentage')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $paymentTrendRows = (clone $capturedPayments)
            ->selectRaw('DATE(verified_at) as day, SUM(amount_paise) / 100.0 as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $courseTrendRows = CourseEnrollment::query()
            ->whereIn('status', ['active', 'completed'])
            ->whereBetween('enrolled_at', [$from, $to])
            ->selectRaw('DATE(enrolled_at) as day, COUNT(*) as enrollments')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $seriesTrendRows = TestSeriesEnrollment::query()
            ->whereIn('status', ['active', 'completed'])
            ->whereBetween('enrolled_at', [$from, $to])
            ->selectRaw('DATE(enrolled_at) as day, COUNT(*) as enrollments')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $trend = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $lastDay = Carbon::parse($to)->startOfDay();
        while ($cursor->lte($lastDay)) {
            $day = $cursor->toDateString();
            $trend[] = [
                'date' => $day,
                'label' => $cursor->format($days <= 30 ? 'd M' : 'd M'),
                'attempts' => (int) data_get($attemptTrendRows->get($day), 'attempts', 0),
                'average_percentage' => round((float) data_get($attemptTrendRows->get($day), 'average_percentage', 0), 1),
                'revenue' => round((float) data_get($paymentTrendRows->get($day), 'revenue', 0), 2),
                'course_enrollments' => (int) data_get($courseTrendRows->get($day), 'enrollments', 0),
                'series_enrollments' => (int) data_get($seriesTrendRows->get($day), 'enrollments', 0),
            ];
            $cursor->addDay();
        }

        $topExams = DB::table('exam_attempts')
            ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
            ->where('exam_attempts.status', 'submitted')
            ->whereBetween('exam_attempts.submitted_at', [$from, $to])
            ->select('exams.id', 'exams.title')
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('COUNT(DISTINCT exam_attempts.user_id) as students_count')
            ->selectRaw('AVG(CASE WHEN exam_attempts.total_marks > 0 THEN (exam_attempts.score / exam_attempts.total_marks) * 100 ELSE NULL END) as average_percentage')
            ->groupBy('exams.id', 'exams.title')
            ->orderByDesc('attempts_count')
            ->limit(8)
            ->get()
            ->map(fn ($exam) => [
                'id' => $exam->id,
                'title' => $exam->title,
                'attempts' => (int) $exam->attempts_count,
                'students' => (int) $exam->students_count,
                'average_percentage' => round((float) ($exam->average_percentage ?? 0), 1),
            ]);

        $recentAttempts = (clone $submittedAttempts)
            ->with(['user:id,name', 'exam:id,title'])
            ->latest('submitted_at')
            ->limit(8)
            ->get(['id', 'user_id', 'exam_id', 'score', 'total_marks', 'submitted_at'])
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'student' => $attempt->user?->name ?? 'Deleted user',
                'exam' => $attempt->exam?->title ?? 'Deleted exam',
                'score' => (float) $attempt->score,
                'total_marks' => (float) $attempt->total_marks,
                'percentage' => $attempt->total_marks > 0 ? round(((float) $attempt->score / (float) $attempt->total_marks) * 100, 1) : null,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ]);

        $recentPayments = (clone $capturedPayments)
            ->with(['user:id,name', 'courseEnrollment.course:id,title', 'enrollment.series:id,title'])
            ->latest('verified_at')
            ->limit(8)
            ->get(['id', 'user_id', 'course_enrollment_id', 'test_series_enrollment_id', 'amount_paise', 'currency', 'verified_at'])
            ->map(function ($payment) {
                $item = $payment->courseEnrollment?->course?->title
                    ?? $payment->enrollment?->series?->title
                    ?? 'Learning purchase';

                return [
                    'id' => $payment->id,
                    'student' => $payment->user?->name ?? 'Deleted user',
                    'item' => $item,
                    'amount' => round($payment->amount_paise / 100, 2),
                    'currency' => $payment->currency,
                    'verified_at' => $payment->verified_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Reports/Index', [
            'filters' => ['days' => $days],
            'summary' => [
                'students_total' => $studentsTotal,
                'new_students' => $newStudents,
                'attempts' => (int) ($attemptSummary->attempts_count ?? 0),
                'average_percentage' => round((float) ($attemptSummary->average_percentage ?? 0), 1),
                'revenue' => round(((float) $capturedPayments->sum('amount_paise')) / 100, 2),
                'captured_payments' => (int) $capturedPayments->count(),
                'course_enrollments' => $courseEnrollments,
                'series_enrollments' => $seriesEnrollments,
            ],
            'trend' => $trend,
            'top_exams' => $topExams,
            'recent_attempts' => $recentAttempts,
            'recent_payments' => $recentPayments,
        ]);
    }
}
