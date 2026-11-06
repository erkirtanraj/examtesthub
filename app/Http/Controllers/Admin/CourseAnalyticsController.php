<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseAnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ]);
        $days = (int) ($filters['days'] ?? 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $enrollments = CourseEnrollment::query()
            ->whereIn('status', ['active', 'completed']);
        $periodEnrollments = (clone $enrollments)->whereBetween('enrolled_at', [$from, $to]);

        $totalEnrollments = (clone $enrollments)->count();
        $completedEnrollments = (clone $enrollments)->where('status', 'completed')->count();
        $activeEnrollments = (clone $enrollments)->where('status', 'active')->count();

        $courses = Course::query()
            ->withCount([
                'enrollments as enrollments_count' => fn ($query) => $query->whereIn('status', ['active', 'completed']),
                'enrollments as completed_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->withCount(['sections'])
            ->whereIn('status', ['published', 'draft'])
            ->orderByDesc('enrollments_count')
            ->orderBy('title')
            ->get()
            ->map(function (Course $course) {
                $lessons = CourseLesson::query()
                    ->where('status', 'published')
                    ->whereHas('chapter.section', fn ($query) => $query->where('course_id', $course->id))
                    ->count();
                $enrolled = (int) $course->enrollments_count;
                $completed = (int) $course->completed_count;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status,
                    'sections' => (int) $course->sections_count,
                    'lessons' => $lessons,
                    'enrollments' => $enrolled,
                    'completed' => $completed,
                    'active' => max(0, $enrolled - $completed),
                    'completion_rate' => $enrolled > 0 ? round($completed * 100 / $enrolled, 1) : 0,
                ];
            });

        $recentEnrollments = (clone $periodEnrollments)
            ->with(['user:id,name,email', 'course:id,title'])
            ->latest('enrolled_at')
            ->limit(10)
            ->get()
            ->map(fn (CourseEnrollment $enrollment) => [
                'id' => $enrollment->id,
                'student' => $enrollment->user?->name ?? 'Deleted user',
                'email' => $enrollment->user?->email,
                'course' => $enrollment->course?->title ?? 'Deleted course',
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
            ]);

        $stalledQuery = DB::table('course_enrollments as ce')
            ->join('courses as c', 'c.id', '=', 'ce.course_id')
            ->join('users as u', 'u.id', '=', 'ce.user_id')
            ->leftJoinSub(
                DB::table('course_lesson_progress')
                    ->select('course_enrollment_id')
                    ->selectRaw('MAX(COALESCE(updated_at, started_at, created_at)) as last_activity_at')
                    ->groupBy('course_enrollment_id'),
                'activity',
                'activity.course_enrollment_id',
                '=',
                'ce.id'
            )
            ->where('ce.status', 'active')
            ->whereNotNull('ce.enrolled_at')
            ->whereRaw('COALESCE(activity.last_activity_at, ce.enrolled_at) <= ?', [now()->subDays(7)]);

        $stalledCount = (clone $stalledQuery)->count();
        $stalledEnrollments = (clone $stalledQuery)
            ->orderByRaw('COALESCE(activity.last_activity_at, ce.enrolled_at) ASC')
            ->limit(10)
            ->get([
                'ce.id', 'u.name as student', 'u.email', 'c.title as course',
                'ce.enrolled_at', 'activity.last_activity_at',
            ])
            ->map(fn ($row) => [
                'id' => $row->id,
                'student' => $row->student,
                'email' => $row->email,
                'course' => $row->course,
                'enrolled_at' => $row->enrolled_at,
                'last_activity_at' => $row->last_activity_at,
            ]);

        return Inertia::render('Admin/CourseAnalytics/Index', [
            'filters' => ['days' => $days],
            'summary' => [
                'courses' => $courses->count(),
                'enrollments' => $totalEnrollments,
                'new_enrollments' => (clone $periodEnrollments)->count(),
                'active_enrollments' => $activeEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'completion_rate' => $totalEnrollments > 0 ? round($completedEnrollments * 100 / $totalEnrollments, 1) : 0,
                'stalled_enrollments' => $stalledCount,
            ],
            'courses' => $courses,
            'recent_enrollments' => $recentEnrollments,
            'stalled_enrollments' => $stalledEnrollments,
        ]);
    }
}
