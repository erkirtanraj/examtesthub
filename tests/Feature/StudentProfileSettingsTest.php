<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfileSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeUser(string $roleSlug, string $email, array $permissionSlugs = []): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => str($roleSlug)->headline()->toString()],
        );

        if ($permissionSlugs !== []) {
            $ids = collect($permissionSlugs)->map(function (string $slug) {
                return Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => str($slug)->replace('.', ' ')->headline()->toString()],
                )->id;
            });
            $role->permissions()->syncWithoutDetaching($ids);
        }

        $user = User::create([
            'name' => 'Profile Test User',
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'preferred_locale' => 'en',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_student_can_view_and_update_their_own_profile(): void
    {
        $student = $this->makeUser('student', 'profile-student@example.test', ['profile.manage']);

        $this->actingAs($student)
            ->get('/student/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Profile/Index')
                ->where('user.email', 'profile-student@example.test')
                ->where('settings.allow_profile_updates', true)
            );

        $this->actingAs($student)->put('/student/profile', [
            'name' => 'Updated Student',
            'email' => 'updated-student@example.test',
            'preferred_locale' => 'hi',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Updated Student',
            'email' => 'updated-student@example.test',
            'preferred_locale' => 'hi',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $student->id,
            'subject_user_id' => $student->id,
            'action' => 'student.profile.updated',
        ]);
    }

    public function test_admin_can_configure_which_student_profile_fields_are_editable(): void
    {
        $admin = $this->makeUser('admin', 'profile-settings-admin@example.test', ['settings.manage']);

        $this->actingAs($admin)
            ->get('/admin/settings/student-profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/StudentProfile')
                ->where('settings.allow_email_edit', true)
            );

        $this->actingAs($admin)->put('/admin/settings/student-profile', [
            'allow_profile_updates' => true,
            'allow_name_edit' => true,
            'allow_email_edit' => false,
            'allow_language_edit' => false,
            'allow_password_change' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_profile_settings', [
            'id' => 1,
            'allow_profile_updates' => 1,
            'allow_email_edit' => 0,
            'allow_language_edit' => 0,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'settings.student_profile_updated',
        ]);
    }

    public function test_student_cannot_change_fields_disabled_by_admin(): void
    {
        $student = $this->makeUser('student', 'locked-fields@example.test', ['profile.manage']);
        StudentProfileSetting::current()->update([
            'allow_email_edit' => false,
            'allow_language_edit' => false,
        ]);

        $this->actingAs($student)->put('/student/profile', [
            'name' => 'Name Can Change',
            'email' => 'tampered-email@example.test',
            'preferred_locale' => 'hi',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Name Can Change',
            'email' => 'locked-fields@example.test',
            'preferred_locale' => 'en',
        ]);
    }

    public function test_student_cannot_update_profile_when_admin_disables_profile_updates(): void
    {
        $student = $this->makeUser('student', 'profile-disabled@example.test', ['profile.manage']);
        StudentProfileSetting::current()->update(['allow_profile_updates' => false]);

        $this->actingAs($student)->put('/student/profile', [
            'name' => 'Should Not Save',
            'email' => 'profile-disabled@example.test',
            'preferred_locale' => 'en',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Profile Test User',
        ]);
    }

    public function test_student_can_change_password_after_current_password_verification(): void
    {
        $student = $this->makeUser('student', 'profile-password@example.test', ['profile.manage']);

        $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'StrongPassword!2026',
            'password' => 'AnotherStrong!2026',
            'password_confirmation' => 'AnotherStrong!2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('AnotherStrong!2026', $student->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $student->id,
            'action' => 'student.profile.password_changed',
        ]);
    }

    public function test_password_change_can_be_disabled_by_admin(): void
    {
        $student = $this->makeUser('student', 'profile-password-locked@example.test', ['profile.manage']);
        StudentProfileSetting::current()->update(['allow_password_change' => false]);

        $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'StrongPassword!2026',
            'password' => 'AnotherStrong!2026',
            'password_confirmation' => 'AnotherStrong!2026',
        ])->assertForbidden();

        $this->assertTrue(Hash::check('StrongPassword!2026', $student->fresh()->password));
    }

    public function test_user_without_settings_permission_cannot_manage_student_profile_settings(): void
    {
        $admin = $this->makeUser('content-manager', 'no-profile-settings@example.test');

        $this->actingAs($admin)->get('/admin/settings/student-profile')->assertForbidden();
        $this->actingAs($admin)->put('/admin/settings/student-profile', [])->assertForbidden();
    }
}
