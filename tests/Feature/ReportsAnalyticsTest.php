<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(string $role, string $email, array $permissions = []): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $user = User::create([
            'name' => 'Analytics Test User',
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($roleModel);

        if ($permissions) {
            $ids = collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => str($slug)->replace('.', ' ')->headline()->toString()]
            )->id)->all();
            $roleModel->permissions()->syncWithoutDetaching($ids);
        }

        return $user;
    }

    private function submittedAttempt(User $student, string $title, float $score, float $total): ExamAttempt
    {
        $exam = Exam::create([
            'title' => $title,
            'slug' => str()->slug($title).'-'.uniqid(),
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'published',
        ]);

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'question_order' => [],
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinute(),
            'submitted_at' => now(),
            'score' => $score,
            'total_marks' => $total,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'unanswered_count' => 0,
        ]);
    }

    public function test_reports_require_reports_view_permission(): void
    {
        $admin = $this->user('admin', 'reports-no-access@example.test');

        $this->actingAs($admin)->get('/admin/reports')->assertForbidden();
    }

    public function test_admin_report_aggregates_only_submitted_attempts_and_captured_payments(): void
    {
        $admin = $this->user('admin', 'reports-admin@example.test', ['reports.view']);
        $student = $this->user('student', 'reports-student@example.test');
        $this->submittedAttempt($student, 'Arithmetic Mock Test', 8, 10);

        $pendingSeries = TestSeries::create([
            'title' => 'Analytics Series',
            'slug' => 'analytics-series-'.uniqid(),
            'access_type' => 'paid',
            'price' => 100,
            'currency' => 'INR',
            'status' => 'published',
        ]);
        $enrollment = TestSeriesEnrollment::create([
            'test_series_id' => $pendingSeries->id,
            'user_id' => $student->id,
            'status' => 'active',
            'amount_paid' => 100,
            'currency' => 'INR',
            'enrolled_at' => now(),
        ]);
        Payment::create([
            'test_series_enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'provider' => 'razorpay',
            'receipt' => 'reports-receipt-'.uniqid(),
            'amount_paise' => 10000,
            'currency' => 'INR',
            'status' => 'captured',
            'verified_at' => now(),
        ]);

        Payment::create([
            'test_series_enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'provider' => 'razorpay',
            'receipt' => 'reports-pending-'.uniqid(),
            'amount_paise' => 5000,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get('/admin/reports?days=30')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports/Index')
                ->where('summary.students_total', 1)
                ->where('summary.new_students', 1)
                ->where('summary.attempts', 1)
                ->where('summary.average_percentage', 80.0)
                ->where('summary.revenue', 100.0)
                ->where('summary.captured_payments', 1)
                ->where('summary.series_enrollments', 1)
                ->where('top_exams.0.title', 'Arithmetic Mock Test')
                ->where('top_exams.0.students', 1)
            );
    }

    public function test_admin_report_rejects_unsupported_date_windows(): void
    {
        $admin = $this->user('super-admin', 'reports-date-admin@example.test');

        $this->actingAs($admin)->get('/admin/reports?days=999')->assertSessionHasErrors('days');
    }

    public function test_student_performance_contains_only_the_students_own_submitted_attempts(): void
    {
        $student = $this->user('student', 'performance-student@example.test', ['results.view-own']);
        $other = $this->user('student', 'performance-other@example.test');
        $this->submittedAttempt($student, 'My Mock Test', 9, 10);
        $this->submittedAttempt($other, 'Other Student Test', 2, 10);

        $this->actingAs($student)->get('/student/performance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Performance/Index')
                ->where('summary.attempts', 1)
                ->where('summary.average_percentage', 90.0)
                ->where('summary.best_percentage', 90.0)
                ->where('recent_attempts.0.exam', 'My Mock Test')
                ->has('recent_attempts', 1)
            );
    }

    public function test_student_performance_requires_results_view_own_permission(): void
    {
        $student = $this->user('student', 'performance-no-permission@example.test');

        $this->actingAs($student)->get('/student/performance')->assertForbidden();
    }
}
