<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\ExamAttempt;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentManagementController extends Controller
{
    private function studentsQuery(array $filters): Builder
    {
        $activity = DB::table('exam_attempts')
            ->where('status', 'submitted')
            ->whereNotNull('submitted_at')
            ->select('user_id')
            ->selectRaw('COUNT(*) as submitted_attempts')
            ->selectRaw('MAX(submitted_at) as last_exam_at')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as average_percentage')
            ->groupBy('user_id');

        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
            ->leftJoinSub($activity, 'student_activity', fn ($join) => $join->on('student_activity.user_id', '=', 'users.id'))
            ->select('users.*')
            ->addSelect([
                DB::raw('COALESCE(student_activity.submitted_attempts, 0) as submitted_attempts'),
                'student_activity.last_exam_at',
                'student_activity.average_percentage',
            ])
            ->with('roles:id,name,slug')
            ->when($filters['search'] ?? null, fn ($query, $term) => $query->where(fn ($where) => $where->where('users.name', 'like', "%{$term}%")->orWhere('users.email', 'like', "%{$term}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('users.is_active', $status === 'active'))
            ->when($filters['activity'] ?? null, function ($query, $activityFilter) {
                if ($activityFilter === 'inactive') {
                    $query->where(fn ($where) => $where->whereNull('student_activity.last_exam_at')->orWhere('student_activity.last_exam_at', '<', now()->subDays(30)));
                } elseif ($activityFilter === 'active') {
                    $query->where('student_activity.last_exam_at', '>=', now()->subDays(30));
                } elseif ($activityFilter === 'low_performing') {
                    $query->whereNotNull('student_activity.average_percentage')->where('student_activity.average_percentage', '<', 40);
                }
            })
            ->orderByRaw('student_activity.last_exam_at IS NULL DESC')
            ->orderBy('users.id', 'desc');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'activity' => ['nullable', 'in:active,inactive,low_performing'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $students = $this->studentsQuery($filters)->paginate($filters['per_page'] ?? 25)->withQueryString();

        $students->getCollection()->transform(function (User $student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'is_active' => $student->is_active,
                'created_at' => $student->created_at?->toIso8601String(),
                'submitted_attempts' => (int) $student->submitted_attempts,
                'last_exam_at' => $student->last_exam_at,
                'average_percentage' => $student->average_percentage === null ? null : round((float) $student->average_percentage, 1),
            ];
        });

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'summary' => [
                'total' => User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))->count(),
                'active' => User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))->where('is_active', true)->count(),
                'inactive' => User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))->where('is_active', false)->count(),
                'low_performing' => (clone $this->studentsQuery([]))->whereNotNull('student_activity.average_percentage')->where('student_activity.average_percentage', '<', 40)->count(),
            ],
        ]);
    }

    public function show(User $user): Response
    {
        abort_unless($user->hasRole('student'), 404);

        $attempts = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'submitted')
            ->whereNotNull('submitted_at');

        $summary = (clone $attempts)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('AVG(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as average_percentage')
            ->selectRaw('MAX(CASE WHEN total_marks > 0 THEN score * 100.0 / total_marks ELSE NULL END) as best_percentage')
            ->selectRaw('MAX(submitted_at) as last_exam_at')
            ->first();

        $recentAttempts = (clone $attempts)->with('exam:id,title')->latest('submitted_at')->limit(30)
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

        $courses = CourseEnrollment::query()->where('user_id', $user->id)->with('course:id,title')
            ->withCount(['progress as completed_lessons_count' => fn ($query) => $query->where('status', 'completed')])
            ->latest('enrolled_at')->get(['id', 'course_id', 'status', 'enrolled_at', 'completed_at']);

        $series = TestSeriesEnrollment::query()->where('user_id', $user->id)->with('series:id,title')
            ->latest('enrolled_at')->get(['id', 'test_series_id', 'status', 'enrolled_at', 'completed_at']);

        $subjectPerformance = DB::table('exam_attempt_answers as answers')
            ->join('exam_attempts as attempts', 'attempts.id', '=', 'answers.exam_attempt_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->where('attempts.user_id', $user->id)->where('attempts.status', 'submitted')
            ->selectRaw("COALESCE(subjects.name, 'Uncategorized') as subject")
            ->selectRaw('COUNT(*) as responses')
            ->selectRaw('SUM(CASE WHEN answers.answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered')
            ->selectRaw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->groupBy('subjects.name')->orderBy('subject')->get()
            ->map(fn ($row) => [
                'subject' => $row->subject,
                'responses' => (int) $row->responses,
                'answered' => (int) $row->answered,
                'correct' => (int) $row->correct,
                'accuracy' => (int) $row->answered > 0 ? round(((int) $row->correct / (int) $row->answered) * 100, 1) : null,
            ]);

        return Inertia::render('Admin/Students/Show', [
            'student' => $user->only('id', 'name', 'email', 'preferred_locale', 'is_active', 'deactivated_at', 'created_at'),
            'summary' => [
                'attempts' => (int) ($summary->attempts ?? 0),
                'average_percentage' => $summary->average_percentage === null ? null : round((float) $summary->average_percentage, 1),
                'best_percentage' => $summary->best_percentage === null ? null : round((float) $summary->best_percentage, 1),
                'last_exam_at' => $summary->last_exam_at,
                'inactive_days' => $summary->last_exam_at ? now()->parse($summary->last_exam_at)->diffInDays(now()) : null,
            ],
            'recent_attempts' => $recentAttempts,
            'subject_performance' => $subjectPerformance,
            'courses' => $courses->map(fn ($enrollment) => [
                'title' => $enrollment->course?->title ?? 'Deleted course',
                'status' => $enrollment->status,
                'completed_lessons' => (int) $enrollment->completed_lessons_count,
                'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                'completed_at' => $enrollment->completed_at?->toIso8601String(),
            ]),
            'test_series' => $series->map(fn ($enrollment) => [
                'title' => $enrollment->series?->title ?? 'Deleted test series',
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                'completed_at' => $enrollment->completed_at?->toIso8601String(),
            ]),
        ]);
    }

    private function safeCsvValue(?string $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\\-@\\t\\r\\n]/', $value) ? "'".$value : $value;
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $filename = 'examtesthub-students-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Student ID', 'Name', 'Email', 'Account Status', 'Submitted Attempts', 'Average Score %', 'Last Exam At', 'Registered At']);
            $this->studentsQuery($filters)->chunk(500, function ($students) use ($out) {
                foreach ($students as $student) {
                    fputcsv($out, [
                        $student->id, $this->safeCsvValue($student->name), $this->safeCsvValue($student->email), $student->is_active ? 'active' : 'inactive',
                        (int) $student->submitted_attempts,
                        $student->average_percentage === null ? '' : round((float) $student->average_percentage, 1),
                        $student->last_exam_at, $student->created_at?->toISOString(),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
