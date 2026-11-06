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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseAnalyticsTest extends TestCase
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
            'name' => 'Analytics User',
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

    private function courseWithLessons(string $slug, int $lessonCount = 2): array
    {
        $course = Course::create([
            'title' => 'Analytics '.$slug, 'slug' => $slug,
            'access_type' => 'free', 'price' => 0, 'currency' => 'INR',
            'status' => 'published', 'published_at' => now(),
        ]);
        $section = CourseSection::create(['course_id' => $course->id, 'title' => 'Section', 'position' => 1]);
        $chapter = CourseChapter::create(['section_id' => $section->id, 'title' => 'Chapter', 'position' => 1]);
        $lessons = collect(range(1, $lessonCount))->map(fn ($position) => CourseLesson::create([
            'chapter_id' => $chapter->id, 'title' => 'Lesson '.$position, 'type' => 'text',
            'content' => 'Lesson content', 'status' => 'published', 'position' => $position,
        ]));
        return [$course, $lessons];
    }

    public function test_course_analytics_requires_reports_permission(): void
    {
        $admin = $this->user('admin', 'course-analytics-denied@example.test');
        $this->actingAs($admin)->get('/admin/course-analytics')->assertForbidden();
    }

    public function test_course_analytics_reports_enrollment_completion_and_stalled_activity(): void
    {
        $admin = $this->user('admin', 'course-analytics-admin@example.test', ['reports.view']);
        $student = $this->user('student', 'course-analytics-student@example.test');
        [$course, $lessons] = $this->courseWithLessons('analytics-course');
        $completed = CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $student->id, 'status' => 'completed',
            'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now()->subDays(2), 'completed_at' => now(),
        ]);
        CourseLessonProgress::create([
            'course_enrollment_id' => $completed->id, 'lesson_id' => $lessons[0]->id,
            'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
        ]);
        CourseLessonProgress::create([
            'course_enrollment_id' => $completed->id, 'lesson_id' => $lessons[1]->id,
            'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
        ]);

        $inactive = $this->user('student', 'course-analytics-inactive@example.test');
        CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $inactive->id, 'status' => 'active',
            'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now()->subDays(10),
        ]);

        $this->actingAs($admin)->get('/admin/course-analytics?days=30')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CourseAnalytics/Index')
                ->where('summary.courses', 1)
                ->where('summary.enrollments', 2)
                ->where('summary.completed_enrollments', 1)
                ->where('summary.active_enrollments', 1)
                ->where('summary.completion_rate', 50)
                ->where('summary.stalled_enrollments', 1)
                ->where('courses.0.lessons', 2)
                ->where('courses.0.completion_rate', 50)
                ->has('recent_enrollments', 2)
                ->has('stalled_enrollments', 1));
    }

    public function test_student_course_progress_contains_only_own_enrollments_and_published_lessons(): void
    {
        $student = $this->user('student', 'course-progress-owner@example.test', ['results.view-own']);
        $other = $this->user('student', 'course-progress-other@example.test');
        [$course, $lessons] = $this->courseWithLessons('student-progress-course');
        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active',
            'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now(),
        ]);
        CourseLessonProgress::create([
            'course_enrollment_id' => $enrollment->id, 'lesson_id' => $lessons[0]->id,
            'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id, 'user_id' => $other->id, 'status' => 'completed',
            'amount_paid' => 0, 'currency' => 'INR', 'enrolled_at' => now(),
        ]);

        $this->actingAs($student)->get('/student/performance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Performance/Index')
                ->where('summary.course_enrollments', 1)
                ->has('course_progress', 1)
                ->where('course_progress.0.course', 'Analytics student-progress-course')
                ->where('course_progress.0.total_lessons', 2)
                ->where('course_progress.0.completed_lessons', 1)
                ->where('course_progress.0.progress_percentage', 50));
    }
}
