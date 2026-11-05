<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Permission;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuestionBankExamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function userWithRole(string $slug, string $email): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => str($slug)->headline()->toString()]);
        $user = User::create([
            'name' => 'Test '.$slug,
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function subject(): Subject
    {
        return Subject::create(['name' => 'Mathematics', 'slug' => 'mathematics', 'is_active' => true]);
    }

    private function publishedQuestion(Subject $subject, User $creator): Question
    {
        return Question::create([
            'subject_id' => $subject->id,
            'created_by' => $creator->id,
            'type' => 'single_choice',
            'prompt' => 'What is 2 + 2?',
            'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'],
            'correct_answer' => ['B'],
            'explanation' => 'Two plus two equals four.',
            'difficulty' => 'easy',
            'language' => 'en',
            'marks' => 1,
            'negative_marks' => 0.25,
            'status' => 'published',
        ]);
    }

    public function test_student_cannot_access_question_bank_or_exam_management(): void
    {
        $student = $this->userWithRole('student', 'phase3-student@example.test');

        $this->actingAs($student)->get('/admin/questions')->assertForbidden();
        $this->actingAs($student)->get('/admin/exams')->assertForbidden();
        $this->actingAs($student)->post('/admin/questions', [])->assertForbidden();
    }

    public function test_super_admin_can_create_question_and_audit_it(): void
    {
        $admin = $this->userWithRole('super-admin', 'phase3-root@example.test');
        $subject = $this->subject();

        $this->actingAs($admin)->post('/admin/questions', [
            'subject_id' => $subject->id,
            'topic_id' => null,
            'type' => 'single_choice',
            'prompt' => 'What is 2 + 2?',
            'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'],
            'correct_answer' => ['B'],
            'explanation' => 'Two plus two equals four.',
            'difficulty' => 'easy',
            'language' => 'en',
            'marks' => 1,
            'negative_marks' => 0.25,
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('questions', ['subject_id' => $subject->id, 'status' => 'published']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'question.created']);
    }

    public function test_super_admin_can_create_exam_from_published_questions(): void
    {
        $admin = $this->userWithRole('super-admin', 'phase3-exam-root@example.test');
        $question = $this->publishedQuestion($this->subject(), $admin);

        $this->actingAs($admin)->post('/admin/exams', [
            'title' => 'Mathematics Practice Test',
            'description' => 'A short practice examination.',
            'instructions' => 'Choose one answer.',
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'starts_at' => null,
            'ends_at' => null,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'draft',
            'question_ids' => [(string) $question->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exams', ['title' => 'Mathematics Practice Test', 'status' => 'draft']);
        $this->assertDatabaseHas('exam_question', ['question_id' => $question->id, 'position' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'exam.created']);
    }

    public function test_exam_cannot_select_unpublished_questions(): void
    {
        $admin = $this->userWithRole('super-admin', 'phase3-draft-root@example.test');
        $subject = $this->subject();
        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'type' => 'short_answer',
            'prompt' => 'Define a prime number.',
            'options' => null,
            'correct_answer' => ['A number greater than one with exactly two factors'],
            'difficulty' => 'medium',
            'language' => 'en',
            'marks' => 2,
            'negative_marks' => 0,
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->post('/admin/exams', [
            'title' => 'Draft Question Test',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'draft',
            'question_ids' => [$question->id],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('exams', ['title' => 'Draft Question Test']);
    }

    public function test_question_used_in_published_exam_cannot_be_changed(): void
    {
        $admin = $this->userWithRole('super-admin', 'phase3-live-root@example.test');
        $question = $this->publishedQuestion($this->subject(), $admin);
        $exam = Exam::create([
            'title' => 'Live Examination',
            'slug' => 'live-examination-test',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'published',
            'created_by' => $admin->id,
        ]);
        $exam->questions()->attach($question->id, ['position' => 1, 'marks' => 1, 'negative_marks' => 0]);

        $this->actingAs($admin)->put('/admin/questions/'.$question->id, [
            'subject_id' => $question->subject_id,
            'topic_id' => null,
            'type' => 'single_choice',
            'prompt' => 'Changed live question?',
            'options' => ['A' => '3', 'B' => '4'],
            'correct_answer' => ['B'],
            'explanation' => null,
            'difficulty' => 'easy',
            'language' => 'en',
            'marks' => 1,
            'negative_marks' => 0,
            'status' => 'published',
        ])->assertStatus(422);

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'prompt' => 'What is 2 + 2?']);
    }

    public function test_exam_manager_permission_is_required_for_exam_routes(): void
    {
        $manager = $this->userWithRole('exam-manager', 'phase3-exam-manager@example.test');
        $role = $manager->roles()->first();
        $permission = Permission::firstOrCreate(['slug' => 'tests.manage'], ['name' => 'Manage tests']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->actingAs($manager)->get('/admin/exams')->assertOk();
    }
}
