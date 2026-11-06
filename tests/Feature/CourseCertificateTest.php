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
use Tests\TestCase;

class CourseCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function student(): User
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $permission = Permission::firstOrCreate(['slug' => 'courses.view'], ['name' => 'View courses']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user = User::create([
            'name' => 'Certificate Student',
            'email' => 'certificate-student@example.test',
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    private function completedEnrollment(User $student, bool $completed): array
    {
        $course = Course::create([
            'title' => 'Certificate Course', 'slug' => 'certificate-course',
            'access_type' => 'free', 'price' => 0, 'currency' => 'INR',
            'status' => 'published', 'published_at' => now(),
        ]);
        $section = CourseSection::create(['course_id' => $course->id, 'title' => 'Section', 'position' => 1]);
        $chapter = CourseChapter::create(['section_id' => $section->id, 'title' => 'Chapter', 'position' => 1]);
        $lesson = CourseLesson::create([
            'chapter_id' => $chapter->id, 'title' => 'Lesson', 'type' => 'text',
            'content' => 'Lesson content', 'status' => 'published', 'position' => 1,
        ]);
        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $student->id,
            'status' => $completed ? 'completed' : 'active',
            'amount_paid' => 0, 'currency' => 'INR',
            'enrolled_at' => now(), 'completed_at' => $completed ? now() : null,
        ]);
        if ($completed) {
            CourseLessonProgress::create([
                'course_enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id,
                'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
            ]);
        }
        return [$course, $enrollment];
    }

    public function test_completed_student_receives_a_persistent_verifiable_certificate(): void
    {
        $student = $this->student();
        [$course] = $this->completedEnrollment($student, true);

        $response = $this->actingAs($student)->get('/student/courses/'.$course->slug.'/certificate');
        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Student/Courses/Certificate')
            ->where('student.name', 'Certificate Student')
            ->where('course.title', 'Certificate Course')
            ->has('certificate.number'));

        $certificate = \App\Models\CourseCertificate::firstOrFail();
        $this->assertSame(1, \App\Models\CourseCertificate::count());

        $this->get('/certificates/verify/'.$certificate->certificate_number)
            ->assertOk()->assertInertia(fn ($page) => $page
                ->component('Public/CertificateVerification')
                ->where('valid', true)
                ->where('certificate.student', 'Certificate Student'));
    }

    public function test_student_cannot_receive_a_certificate_before_course_completion(): void
    {
        $student = $this->student();
        [$course] = $this->completedEnrollment($student, false);

        $this->actingAs($student)->get('/student/courses/'.$course->slug.'/certificate')->assertForbidden();
        $this->assertDatabaseCount('course_certificates', 0);
    }
}
