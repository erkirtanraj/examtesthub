<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestSeriesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(string $role, string $email): User
    {
        $r = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $u = User::create([
            'name' => 'Test User', 'email' => $email,
            'password' => Hash::make('StrongPassword!2026'), 'is_active' => true,
        ]);
        $u->roles()->attach($r);

        return $u;
    }

    private function publishedExam(User $admin): Exam
    {
        $subject = Subject::create(['name' => 'Math', 'slug' => 'math']);
        $question = Question::create([
            'subject_id' => $subject->id, 'created_by' => $admin->id,
            'type' => 'single_choice', 'prompt' => '2 + 2?',
            'options' => ['A' => '3', 'B' => '4'], 'correct_answer' => ['B'],
            'difficulty' => 'easy', 'language' => 'en', 'marks' => 2,
            'negative_marks' => 0.5, 'status' => 'published',
        ]);
        $exam = Exam::create([
            'title' => 'Math test', 'slug' => 'math-test-'.uniqid(),
            'duration_minutes' => 30, 'max_attempts' => 1,
            'shuffle_questions' => false, 'shuffle_options' => false,
            'status' => 'published', 'created_by' => $admin->id,
        ]);
        $exam->questions()->attach($question->id, ['position' => 1, 'marks' => 2, 'negative_marks' => 0.5]);

        return $exam;
    }

    private function series(User $admin, Exam $exam, string $access = 'free'): TestSeries
    {
        $series = TestSeries::create([
            'title' => 'Math Series', 'slug' => 'math-series-'.uniqid(),
            'description' => 'Practice package', 'access_type' => $access,
            'price' => $access === 'paid' ? 199 : 0, 'currency' => 'INR',
            'status' => 'published', 'created_by' => $admin->id,
        ]);
        $series->exams()->attach($exam->id, ['position' => 1]);

        return $series;
    }

    public function test_student_can_enroll_in_free_series(): void
    {
        $admin = $this->user('super-admin', 'series-admin1@example.test');
        $student = $this->user('student', 'series-student1@example.test');
        $series = $this->series($admin, $this->publishedExam($admin));

        $this->actingAs($student)->post('/student/test-series/'.$series->id.'/enroll')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('test_series_enrollments', [
            'test_series_id' => $series->id, 'user_id' => $student->id, 'status' => 'active',
        ]);
    }

    public function test_paid_series_enrollment_remains_pending_until_payment_is_verified(): void
    {
        $admin = $this->user('super-admin', 'series-admin2@example.test');
        $student = $this->user('student', 'series-student2@example.test');
        $series = $this->series($admin, $this->publishedExam($admin), 'paid');

        $this->actingAs($student)->post('/student/test-series/'.$series->id.'/enroll')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('test_series_enrollments', [
            'test_series_id' => $series->id, 'user_id' => $student->id, 'status' => 'pending_payment',
        ]);
    }

    public function test_student_without_active_enrollment_cannot_start_series_exam(): void
    {
        $admin = $this->user('super-admin', 'series-admin3@example.test');
        $student = $this->user('student', 'series-student3@example.test');
        $exam = $this->publishedExam($admin);
        $this->series($admin, $exam);

        $this->actingAs($student)->post('/student/exams/'.$exam->id.'/start')->assertForbidden();
    }

    public function test_active_free_enrollment_allows_student_to_start_exam(): void
    {
        $admin = $this->user('super-admin', 'series-admin4@example.test');
        $student = $this->user('student', 'series-student4@example.test');
        $exam = $this->publishedExam($admin);
        $series = $this->series($admin, $exam);
        TestSeriesEnrollment::create([
            'test_series_id' => $series->id, 'user_id' => $student->id,
            'status' => 'active', 'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now(),
        ]);

        $this->actingAs($student)->post('/student/exams/'.$exam->id.'/start')->assertRedirect();
        $this->assertDatabaseHas('exam_attempts', ['exam_id' => $exam->id, 'user_id' => $student->id]);
    }

    public function test_admin_can_create_a_free_test_series_with_published_exams(): void
    {
        $admin = $this->user('super-admin', 'series-admin5@example.test');
        $exam = $this->publishedExam($admin);

        $this->actingAs($admin)->post('/admin/test-series', [
            'title' => 'Foundation Practice Series',
            'description' => 'Starter package',
            'access_type' => 'free', 'price' => 0, 'currency' => 'INR',
            'status' => 'draft', 'starts_at' => null, 'ends_at' => null,
            'max_enrollments' => null, 'exam_ids' => [(string) $exam->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('test_series', ['title' => 'Foundation Practice Series', 'access_type' => 'free']);
        $this->assertDatabaseHas('test_series_exam', ['exam_id' => $exam->id, 'position' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'test_series.created']);
    }
}
