<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\Permission;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBulkContentActionsTest extends TestCase
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
        $user = User::create(['name' => 'Bulk action tester', 'email' => $email, 'password' => Hash::make('StrongPassword!2026'), 'is_active' => true]);
        $user->roles()->attach($roleModel);
        $permissionIds = collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => str($slug)->headline()->toString()])->id)->all();
        if ($permissionIds) $roleModel->permissions()->syncWithoutDetaching($permissionIds);
        return $user;
    }

    private function question(User $admin, Subject $subject, string $prompt): Question
    {
        return Question::create([
            'subject_id' => $subject->id, 'created_by' => $admin->id, 'type' => 'short_answer',
            'prompt' => $prompt, 'options' => null, 'correct_answer' => ['Answer'],
            'difficulty' => 'easy', 'language' => 'en', 'marks' => 1, 'negative_marks' => 0, 'status' => 'draft',
        ]);
    }

    public function test_authorized_admin_can_bulk_delete_questions_by_explicit_ids(): void
    {
        $admin = $this->user('super-admin', 'bulk-admin1@example.test');
        $subject = Subject::create(['name' => 'Math', 'slug' => 'math']);
        $first = $this->question($admin, $subject, 'Question one');
        $second = $this->question($admin, $subject, 'Question two');

        $this->actingAs($admin)->post('/admin/bulk-delete/questions', ['ids' => [$first->id, $second->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('questions', ['id' => $first->id]);
        $this->assertDatabaseMissing('questions', ['id' => $second->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.bulk_deleted']);
    }

    public function test_bulk_delete_rejects_duplicate_ids(): void
    {
        $admin = $this->user('super-admin', 'bulk-admin2@example.test');
        $subject = Subject::create(['name' => 'Science', 'slug' => 'science']);
        $question = $this->question($admin, $subject, 'Question');

        $this->actingAs($admin)->post('/admin/bulk-delete/questions', ['ids' => [$question->id, $question->id]])->assertSessionHasErrors('ids.1');
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_bulk_delete_protects_questions_used_in_exams(): void
    {
        $admin = $this->user('super-admin', 'bulk-admin3@example.test');
        $subject = Subject::create(['name' => 'History', 'slug' => 'history']);
        $question = $this->question($admin, $subject, 'Question in exam');
        $exam = Exam::create([
            'title' => 'History exam', 'slug' => 'history-exam', 'duration_minutes' => 30, 'max_attempts' => 1,
            'shuffle_questions' => false, 'shuffle_options' => false, 'status' => 'draft', 'created_by' => $admin->id,
        ]);
        $exam->questions()->attach($question->id, ['position' => 1, 'marks' => 1, 'negative_marks' => 0]);

        $this->actingAs($admin)->post('/admin/bulk-delete/questions', ['ids' => [$question->id]])->assertUnprocessable();
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_bulk_delete_requires_the_resource_permission(): void
    {
        $manager = $this->user('content-manager', 'bulk-manager@example.test');
        $subject = Subject::create(['name' => 'English', 'slug' => 'english']);
        $question = $this->question($manager, $subject, 'Protected question');

        $this->actingAs($manager)->post('/admin/bulk-delete/questions', ['ids' => [$question->id]])->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_bulk_delete_protects_courses_with_enrollments(): void
    {
        $admin = $this->user('super-admin', 'bulk-admin4@example.test');
        $student = $this->user('student', 'bulk-student@example.test');
        $course = Course::create([
            'title' => 'Enrolled course', 'slug' => 'enrolled-course', 'access_type' => 'free', 'price' => 0,
            'currency' => 'INR', 'status' => 'draft', 'created_by' => $admin->id,
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active',
            'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now(),
        ]);

        $this->actingAs($admin)->post('/admin/bulk-delete/courses', ['ids' => [$course->id]])->assertUnprocessable();
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
