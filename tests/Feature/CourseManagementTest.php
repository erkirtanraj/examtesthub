<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonProgress;
use App\Models\CourseSection;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(string $role, string $email): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $user = User::create([
            'name' => 'Course Test User',
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($roleModel);
        if ($role === 'student') {
            $permission = Permission::firstOrCreate(['slug' => 'courses.view'], ['name' => 'View courses']);
            $roleModel->permissions()->syncWithoutDetaching([$permission->id]);
        }
        return $user;
    }

    private function course(User $admin, string $access = 'free'): Course
    {
        return Course::create([
            'title' => 'Foundation Mathematics',
            'title_hi' => 'गणित की नींव',
            'slug' => 'foundation-math-'.uniqid(),
            'description' => 'A foundation course.',
            'description_hi' => 'एक आधारभूत कोर्स।',
            'access_type' => $access,
            'price' => $access === 'paid' ? 250 : 0,
            'currency' => 'INR',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);
    }

    private function lesson(Course $course, bool $preview = false): CourseLesson
    {
        $section = CourseSection::create(['course_id' => $course->id, 'title' => 'Basics', 'position' => 1]);
        $chapter = CourseChapter::create(['section_id' => $section->id, 'title' => 'Numbers', 'position' => 1]);
        return CourseLesson::create([
            'chapter_id' => $chapter->id, 'title' => 'Introduction', 'title_hi' => 'परिचय',
            'type' => 'text', 'content' => 'Welcome to the course.', 'content_hi' => 'कोर्स में आपका स्वागत है।',
            'is_preview' => $preview, 'duration_minutes' => 5, 'status' => 'published', 'position' => 1,
        ]);
    }

    public function test_admin_can_create_a_draft_course(): void
    {
        $admin = $this->user('super-admin', 'course-admin1@example.test');
        $this->actingAs($admin)->post('/admin/courses', [
            'title' => 'Competitive Maths',
            'title_hi' => 'प्रतियोगी गणित',
            'description' => 'Complete arithmetic course.',
            'description_hi' => 'संपूर्ण अंकगणित कोर्स।',
            'category' => 'Arithmetic',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'INR',
            'status' => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courses', ['title' => 'Competitive Maths', 'status' => 'draft']);
    }

    public function test_new_courses_must_start_as_draft_until_the_curriculum_is_ready(): void
    {
        $admin = $this->user('super-admin', 'course-admin-publish-at-create@example.test');

        $this->actingAs($admin)->post('/admin/courses', [
            'title' => 'Prematurely Published Course',
            'title_hi' => 'समय से पहले प्रकाशित कोर्स',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'INR',
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('courses', ['title' => 'Prematurely Published Course']);
    }

    public function test_course_cannot_be_published_without_a_published_lesson(): void
    {
        $admin = $this->user('super-admin', 'course-admin2@example.test');
        $course = $this->course($admin);
        $this->actingAs($admin)->patch('/admin/courses/'.$course->id.'/status', ['status' => 'published'])
            ->assertUnprocessable();
    }

    public function test_student_can_enroll_and_complete_a_free_course_lesson(): void
    {
        $admin = $this->user('super-admin', 'course-admin3@example.test');
        $student = $this->user('student', 'course-student1@example.test');
        $course = $this->course($admin);
        $lesson = $this->lesson($course);

        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/enroll')->assertRedirect();
        $enrollment = CourseEnrollment::where('course_id', $course->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame('active', $enrollment->status);

        $this->actingAs($student)->get('/student/courses/'.$course->slug.'/lessons/'.$lesson->id)->assertOk();
        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/lessons/'.$lesson->id.'/complete')->assertRedirect();

        $this->assertDatabaseHas('course_lesson_progress', [
            'course_enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('course_enrollments', ['id' => $enrollment->id, 'status' => 'completed']);
    }

    public function test_student_can_access_only_free_preview_before_enrollment(): void
    {
        $admin = $this->user('super-admin', 'course-admin4@example.test');
        $student = $this->user('student', 'course-student2@example.test');
        $course = $this->course($admin);
        $preview = $this->lesson($course, true);
        $locked = CourseLesson::create([
            'chapter_id' => $preview->chapter_id, 'title' => 'Locked lesson',
            'type' => 'text', 'content' => 'Paid content', 'status' => 'published', 'position' => 2,
        ]);

        $this->actingAs($student)->get('/student/courses/'.$course->slug.'/lessons/'.$preview->id)->assertOk();
        $this->actingAs($student)->get('/student/courses/'.$course->slug.'/lessons/'.$locked->id)->assertForbidden();
    }

    public function test_paid_course_cannot_be_enrolled_without_secure_checkout(): void
    {
        $admin = $this->user('super-admin', 'course-admin5@example.test');
        $student = $this->user('student', 'course-student3@example.test');
        $course = $this->course($admin, 'paid');

        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/enroll')->assertConflict();
        $this->assertDatabaseMissing('course_enrollments', ['course_id' => $course->id, 'user_id' => $student->id]);
    }

    public function test_course_outline_does_not_expose_locked_lesson_content(): void
    {
        $admin = $this->user('super-admin', 'course-admin7@example.test');
        $student = $this->user('student', 'course-student5@example.test');
        $course = $this->course($admin, 'paid');
        $preview = $this->lesson($course, true);
        $locked = CourseLesson::create([
            'chapter_id' => $preview->chapter_id,
            'title' => 'Paid lesson',
            'type' => 'text',
            'content' => 'Private paid lesson body',
            'content_hi' => 'निजी पाठ सामग्री',
            'status' => 'published',
            'position' => 2,
        ]);

        $this->actingAs($student)->get('/student/courses/'.$course->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Courses/Show')
                ->where('course.sections.0.chapters.0.lessons.0.is_locked', false)
                ->where('course.sections.0.chapters.0.lessons.1.is_locked', true)
                ->missing('course.sections.0.chapters.0.lessons.0.content')
                ->missing('course.sections.0.chapters.0.lessons.1.content')
                ->missing('course.sections.0.chapters.0.lessons.1.content_hi')
            );
    }

    public function test_course_checkout_retry_reuses_the_existing_pending_razorpay_order(): void
    {
        $admin = $this->user('super-admin', 'course-admin8@example.test');
        $student = $this->user('student', 'course-student6@example.test');
        $course = $this->course($admin, 'paid');
        $this->lesson($course);

        config(['services.razorpay' => [
            'key_id' => 'rzp_test_key', 'key_secret' => 'test_secret',
            'webhook_secret' => 'webhook_secret', 'base_url' => 'https://api.razorpay.com/v1',
        ]]);
        Http::fake([
            '*/v1/orders' => Http::response(['id' => 'order_course_retry'], 200),
        ]);

        $first = $this->actingAs($student)->post('/student/courses/'.$course->slug.'/checkout');
        $payment = \App\Models\Payment::where('provider_order_id', 'order_course_retry')->firstOrFail();
        $first->assertRedirect('/student/payments/'.$payment->id.'/checkout');

        $second = $this->actingAs($student)->post('/student/courses/'.$course->slug.'/checkout');
        $second->assertRedirect('/student/payments/'.$payment->id.'/checkout');

        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_course_checkout_does_not_reset_an_active_enrollment_to_pending(): void
    {
        $admin = $this->user('super-admin', 'course-admin9@example.test');
        $student = $this->user('student', 'course-student7@example.test');
        $course = $this->course($admin, 'paid');
        CourseEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'amount_paid' => 250,
            'currency' => 'INR',
            'enrolled_at' => now(),
        ]);

        config(['services.razorpay' => [
            'key_id' => 'rzp_test_key', 'key_secret' => 'test_secret',
            'webhook_secret' => 'webhook_secret', 'base_url' => 'https://api.razorpay.com/v1',
        ]]);

        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/checkout')->assertConflict();
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active',
        ]);
    }

    public function test_completed_student_can_complete_lessons_added_after_course_completion(): void
    {
        $admin = $this->user('super-admin', 'course-admin10@example.test');
        $student = $this->user('student', 'course-student8@example.test');
        $course = $this->course($admin);
        $first = $this->lesson($course);
        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'amount_paid' => 0,
            'currency' => 'INR',
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);
        CourseLessonProgress::create([
            'course_enrollment_id' => $enrollment->id,
            'lesson_id' => $first->id,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $second = CourseLesson::create([
            'chapter_id' => $first->chapter_id, 'title' => 'New lesson 1',
            'type' => 'text', 'content' => 'New content 1', 'status' => 'published', 'position' => 2,
        ]);
        $third = CourseLesson::create([
            'chapter_id' => $first->chapter_id, 'title' => 'New lesson 2',
            'type' => 'text', 'content' => 'New content 2', 'status' => 'published', 'position' => 3,
        ]);

        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/lessons/'.$second->id.'/complete')->assertRedirect();
        $this->assertDatabaseHas('course_enrollments', ['id' => $enrollment->id, 'status' => 'active', 'completed_at' => null]);

        $this->actingAs($student)->post('/student/courses/'.$course->slug.'/lessons/'.$third->id.'/complete')->assertRedirect();
        $this->assertDatabaseHas('course_enrollments', ['id' => $enrollment->id, 'status' => 'completed']);
    }

    public function test_paid_course_uses_existing_razorpay_payment_flow_and_activates_access(): void
    {
        $admin = $this->user('super-admin', 'course-admin6@example.test');
        $student = $this->user('student', 'course-student4@example.test');
        $course = $this->course($admin, 'paid');
        $this->lesson($course);

        config(['services.razorpay' => [
            'key_id' => 'rzp_test_key', 'key_secret' => 'test_secret',
            'webhook_secret' => 'webhook_secret', 'base_url' => 'https://api.razorpay.com/v1',
        ]]);
        Http::fake([
            '*/v1/orders' => Http::response(['id' => 'order_course_123'], 200),
            '*/v1/payments/pay_course_123' => Http::response([
                'id' => 'pay_course_123', 'order_id' => 'order_course_123',
                'amount' => 25000, 'currency' => 'INR', 'status' => 'captured',
            ], 200),
        ]);

        $response = $this->actingAs($student)->post('/student/courses/'.$course->slug.'/checkout');
        $payment = \App\Models\Payment::where('provider_order_id', 'order_course_123')->firstOrFail();
        $this->assertSame('course_enrollment', $payment->courseEnrollment ? 'course_enrollment' : 'missing');
        $this->assertNull($payment->test_series_enrollment_id);
        $response->assertRedirect('/student/payments/'.$payment->id.'/checkout');

        $signature = hash_hmac('sha256', 'order_course_123|pay_course_123', 'test_secret');
        $this->actingAs($student)->post('/student/payments/'.$payment->id.'/verify', [
            'razorpay_order_id' => 'order_course_123',
            'razorpay_payment_id' => 'pay_course_123',
            'razorpay_signature' => $signature,
        ])->assertRedirect('/student/payments');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'captured']);
        $this->assertDatabaseHas('course_enrollments', [
            'id' => $payment->course_enrollment_id, 'status' => 'active', 'payment_reference' => 'pay_course_123',
        ]);
    }
}
