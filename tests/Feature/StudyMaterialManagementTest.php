<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudyMaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function userWithPermissions(string $role, string $email, array $permissions = []): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $user = User::create([
            'name' => 'Study Material Test User',
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($roleModel);

        $permissionIds = collect($permissions)->map(function (string $slug) {
            return Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => str($slug)->replace('.', ' ')->headline()->toString()]
            )->id;
        })->all();

        if ($permissionIds) {
            $roleModel->permissions()->syncWithoutDetaching($permissionIds);
        }

        return $user;
    }

    private function subject(): Subject
    {
        return Subject::create([
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'is_active' => true,
        ]);
    }

    private function publishedCourse(User $admin): Course
    {
        return Course::create([
            'title' => 'Foundation Mathematics',
            'title_hi' => 'गणित की नींव',
            'slug' => 'foundation-math-'.uniqid(),
            'access_type' => 'paid',
            'price' => 250,
            'currency' => 'INR',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);
    }

    private function material(array $overrides = []): StudyMaterial
    {
        $path = 'study-materials/test-notes.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nStudy notes\n%%EOF");

        return StudyMaterial::create(array_merge([
            'title' => 'Arithmetic Formula Notes',
            'title_hi' => 'अंकगणित सूत्र नोट्स',
            'description' => 'Important arithmetic formulas.',
            'description_hi' => 'महत्वपूर्ण अंकगणित सूत्र।',
            'language' => 'both',
            'status' => 'published',
            'file_path' => $path,
            'original_name' => 'arithmetic-notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_authorized_admin_can_upload_a_private_pdf(): void
    {
        $admin = $this->userWithPermissions('content-manager', 'materials-admin1@example.test', ['materials.manage']);
        $subject = $this->subject();

        $this->actingAs($admin)->post('/admin/materials', [
            'title' => 'Arithmetic Formula Notes',
            'title_hi' => 'अंकगणित सूत्र नोट्स',
            'description' => 'Important formulas.',
            'description_hi' => 'महत्वपूर्ण सूत्र।',
            'subject_id' => $subject->id,
            'language' => 'both',
            'status' => 'published',
            'file' => UploadedFile::fake()->create('arithmetic-notes.pdf', 40, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $material = StudyMaterial::where('title', 'Arithmetic Formula Notes')->firstOrFail();

        $this->assertSame('published', $material->status);
        $this->assertSame('application/pdf', $material->mime_type);
        $this->assertSame($subject->id, $material->subject_id);
        Storage::disk('local')->assertExists($material->file_path);
        $this->assertNotNull($material->published_at);
    }

    public function test_admin_material_routes_require_materials_manage_permission(): void
    {
        $admin = $this->userWithPermissions('content-manager', 'materials-admin2@example.test');

        $this->actingAs($admin)->get('/admin/materials')->assertForbidden();
    }

    public function test_student_can_download_a_published_free_pdf(): void
    {
        $student = $this->userWithPermissions('student', 'materials-student1@example.test', ['courses.view']);
        $material = $this->material();

        $this->actingAs($student)
            ->get('/student/materials/'.$material->id.'/download')
            ->assertDownload('arithmetic-notes.pdf');
    }

    public function test_course_linked_pdf_is_locked_until_student_has_course_enrollment(): void
    {
        $admin = $this->userWithPermissions('super-admin', 'materials-admin3@example.test');
        $student = $this->userWithPermissions('student', 'materials-student2@example.test', ['courses.view']);
        $course = $this->publishedCourse($admin);
        $material = $this->material(['course_id' => $course->id]);

        $this->actingAs($student)->get('/student/materials')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Materials/Index')
                ->where('materials.data.0.can_download', false)
                ->where('materials.data.0.course.slug', $course->slug)
            );

        $this->actingAs($student)
            ->get('/student/materials/'.$material->id.'/download')
            ->assertForbidden();
    }

    public function test_active_course_student_can_download_course_linked_pdf(): void
    {
        $admin = $this->userWithPermissions('super-admin', 'materials-admin4@example.test');
        $student = $this->userWithPermissions('student', 'materials-student3@example.test', ['courses.view']);
        $course = $this->publishedCourse($admin);
        $material = $this->material(['course_id' => $course->id]);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'amount_paid' => 250,
            'currency' => 'INR',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->get('/student/materials/'.$material->id.'/download')
            ->assertDownload('arithmetic-notes.pdf');
    }

    public function test_students_cannot_view_or_download_draft_materials(): void
    {
        $student = $this->userWithPermissions('student', 'materials-student4@example.test', ['courses.view']);
        $material = $this->material(['status' => 'draft', 'published_at' => null]);

        $this->actingAs($student)->get('/student/materials')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Materials/Index')
                ->where('materials.total', 0)
            );

        $this->actingAs($student)
            ->get('/student/materials/'.$material->id.'/download')
            ->assertNotFound();
    }

    public function test_students_without_course_enrollment_cannot_download_after_course_expires(): void
    {
        $admin = $this->userWithPermissions('super-admin', 'materials-admin5@example.test');
        $student = $this->userWithPermissions('student', 'materials-student5@example.test', ['courses.view']);
        $course = $this->publishedCourse($admin);
        $course->update(['ends_at' => now()->subMinute()]);
        $material = $this->material(['course_id' => $course->id]);

        $this->actingAs($student)
            ->get('/student/materials/'.$material->id.'/download')
            ->assertNotFound();
    }
}
