<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Permission;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamAnalyticsTest extends TestCase
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
            'name' => 'Exam Analytics User',
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

    public function test_exam_analytics_requires_reports_permission(): void
    {
        $admin = $this->user('admin', 'exam-analytics-denied@example.test');

        $this->actingAs($admin)->get('/admin/exam-analytics')->assertForbidden();
    }

    public function test_exam_analytics_aggregates_submitted_attempts_and_question_responses(): void
    {
        $admin = $this->user('admin', 'exam-analytics-admin@example.test', ['reports.view']);
        $student = $this->user('student', 'exam-analytics-student@example.test');
        $subject = Subject::create(['name' => 'Mathematics', 'slug' => 'math-analytics']);
        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'type' => 'single_choice',
            'prompt' => 'What is 2 + 2?',
            'options' => ['A' => '3', 'B' => '4'],
            'correct_answer' => ['B'],
            'difficulty' => 'medium',
            'language' => 'en',
            'marks' => 2,
            'negative_marks' => 0,
            'status' => 'published',
        ]);
        $exam = Exam::create([
            'title' => 'Arithmetic Analytics',
            'slug' => 'arithmetic-analytics',
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'published',
        ]);
        $exam->questions()->attach($question->id, ['position' => 1, 'marks' => 2, 'negative_marks' => 0]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'question_order' => [$question->id],
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(1),
            'submitted_at' => now(),
            'score' => 2,
            'total_marks' => 2,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'unanswered_count' => 0,
        ]);
        ExamAttemptAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => ['B'],
            'marked_for_review' => false,
            'is_correct' => true,
            'marks_awarded' => 2,
            'answered_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/exam-analytics?days=30&exam_id='.$exam->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ExamAnalytics/Index')
                ->where('filters.exam_id', $exam->id)
                ->where('summary.attempts', 1)
                ->where('summary.students', 1)
                ->where('summary.average_percentage', 100)
                ->where('summary.correct', 1)
                ->where('question_performance.0.prompt', 'What is 2 + 2?')
                ->where('question_performance.0.accuracy', 100)
                ->where('exam_performance.0.title', 'Arithmetic Analytics')
                ->where('recent_attempts.0.student', 'Exam Analytics User')
            );
    }

    public function test_exam_filter_and_date_window_are_validated(): void
    {
        $admin = $this->user('super-admin', 'exam-analytics-validation@example.test', ['reports.view']);

        $this->actingAs($admin)->get('/admin/exam-analytics?days=365')->assertSessionHasErrors('days');
        $this->actingAs($admin)->get('/admin/exam-analytics?exam_id=999999')->assertSessionHasErrors('exam_id');
    }
}
