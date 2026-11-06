<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
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
        $user = User::create(['name' => 'Test '.$role, 'email' => $email, 'password' => Hash::make('StrongPassword!2026'), 'is_active' => true]);
        $user->roles()->attach($roleModel);
        if ($permissions) {
            $ids = collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => str($slug)->replace('.', ' ')->headline()->toString()])->id)->all();
            $roleModel->permissions()->syncWithoutDetaching($ids);
        }
        return $user;
    }

    private function attempt(User $student, string $title, float $score): ExamAttempt
    {
        $exam = Exam::create(['title' => $title, 'slug' => str()->slug($title).'-'.uniqid(), 'duration_minutes' => 30, 'max_attempts' => 2, 'shuffle_questions' => false, 'shuffle_options' => false, 'status' => 'published']);
        return ExamAttempt::create([
            'exam_id' => $exam->id, 'user_id' => $student->id, 'status' => 'submitted', 'question_order' => [],
            'started_at' => now()->subMinutes(20), 'expires_at' => now()->subMinute(), 'submitted_at' => now(),
            'score' => $score, 'total_marks' => 100, 'correct_count' => 1, 'incorrect_count' => 1, 'unanswered_count' => 0,
        ]);
    }

    public function test_student_directory_filters_students_and_excludes_other_roles(): void
    {
        $admin = $this->user('admin', 'student-dir-admin@example.test', ['users.view']);
        $student = $this->user('student', 'directory-student@example.test');
        $this->user('content-manager', 'directory-manager@example.test');

        $this->actingAs($admin)->get('/admin/students?search=directory-student')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Students/Index')
                ->where('students.total', 1)
                ->where('students.data.0.email', $student->email)
                ->where('summary.total', 1));
    }

    public function test_student_report_shows_exam_history_and_enrollments(): void
    {
        $admin = $this->user('admin', 'student-report-admin@example.test', ['users.view']);
        $student = $this->user('student', 'student-detail@example.test');
        $this->attempt($student, 'Student Algebra Mock', 35);

        $course = Course::create(['title' => 'Algebra Course', 'slug' => 'algebra-course', 'access_type' => 'free', 'price' => 0, 'currency' => 'INR', 'status' => 'published']);
        CourseEnrollment::create(['course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active', 'enrolled_at' => now()]);
        $series = TestSeries::create(['title' => 'Math Series', 'slug' => 'math-series', 'access_type' => 'free', 'price' => 0, 'currency' => 'INR', 'status' => 'published']);
        TestSeriesEnrollment::create(['test_series_id' => $series->id, 'user_id' => $student->id, 'status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Students/Show')
                ->where('student.email', $student->email)
                ->where('summary.attempts', 1)
                ->where('summary.average_percentage', 35)
                ->where('recent_attempts.0.exam', 'Student Algebra Mock')
                ->where('courses.0.title', 'Algebra Course')
                ->where('test_series.0.title', 'Math Series'));
    }

    public function test_student_report_does_not_expose_non_student_accounts(): void
    {
        $admin = $this->user('admin', 'student-report-role-admin@example.test', ['users.view']);
        $other = $this->user('content-manager', 'not-a-student@example.test');
        $this->actingAs($admin)->get('/admin/students/'.$other->id)->assertNotFound();
    }

    public function test_student_directory_requires_users_view_permission(): void
    {
        $admin = $this->user('admin', 'student-no-permission@example.test');
        $this->actingAs($admin)->get('/admin/students')->assertForbidden();
    }
}
