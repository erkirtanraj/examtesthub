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

class StudentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function student(string $email): User
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user = User::create([
            'name' => 'Report Student',
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $permission = Permission::firstOrCreate(['slug' => 'results.view-own'], ['name' => 'View own results']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        return $user;
    }

    public function test_student_report_shows_only_authenticated_students_attempts_and_subject_performance(): void
    {
        $student = $this->student('report-student@example.test');
        $other = $this->student('other-report-student@example.test');
        $subject = Subject::create(['name' => 'Mathematics', 'slug' => 'report-math']);
        $question = Question::create([
            'subject_id' => $subject->id,
            'created_by' => $student->id,
            'type' => 'single_choice',
            'prompt' => 'Two plus two?',
            'options' => ['A' => '3', 'B' => '4'],
            'correct_answer' => ['B'],
            'difficulty' => 'easy',
            'language' => 'en',
            'marks' => 1,
            'negative_marks' => 0,
            'status' => 'published',
        ]);
        $exam = Exam::create([
            'title' => 'Report Exam',
            'slug' => 'report-exam',
            'duration_minutes' => 20,
            'max_attempts' => 2,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'status' => 'published',
        ]);
        $exam->questions()->attach($question->id, ['position' => 1, 'marks' => 1, 'negative_marks' => 0]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'user_id' => $student->id, 'status' => 'submitted',
            'question_order' => [$question->id], 'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinute(), 'submitted_at' => now(),
            'score' => 1, 'total_marks' => 1, 'correct_count' => 1, 'incorrect_count' => 0, 'unanswered_count' => 0,
        ]);
        ExamAttemptAnswer::create([
            'exam_attempt_id' => $attempt->id, 'question_id' => $question->id, 'answer' => ['B'],
            'marked_for_review' => false, 'is_correct' => true, 'marks_awarded' => 1, 'answered_at' => now(),
        ]);
        ExamAttempt::create([
            'exam_id' => $exam->id, 'user_id' => $other->id, 'status' => 'submitted',
            'question_order' => [$question->id], 'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinute(), 'submitted_at' => now(),
            'score' => 0, 'total_marks' => 1, 'correct_count' => 0, 'incorrect_count' => 1, 'unanswered_count' => 0,
        ]);

        $this->actingAs($student)->get('/student/reports?days=90')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Reports/Index')
                ->where('summary.attempts', 1)
                ->where('summary.average_percentage', 100)
                ->where('subject_performance.0.subject', 'Mathematics')
                ->where('subject_performance.0.accuracy', 100)
                ->where('exam_history.0.rank', 1)
                ->where('exam_history.0.cohort_size', 2)
            );
    }

    public function test_student_report_rejects_unsupported_date_ranges(): void
    {
        $student = $this->student('report-range@example.test');
        $this->actingAs($student)->get('/student/reports?days=7')->assertSessionHasErrors('days');
    }
}
