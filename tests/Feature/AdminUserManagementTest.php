<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeUserWithRole(string $roleSlug, string $email): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => str($roleSlug)->headline()->toString()]);
        $user = User::create([
            'name' => 'Test '.$roleSlug,
            'email' => $email,
            'password' => Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_student_cannot_access_admin_user_management(): void
    {
        $student = $this->makeUserWithRole('student', 'student-admin-area@example.test');

        $this->actingAs($student)->get('/admin/users')->assertForbidden();
        $this->actingAs($student)->post('/admin/users', [])->assertForbidden();
    }

    public function test_super_admin_can_create_user_and_audit_action(): void
    {
        $admin = $this->makeUserWithRole('super-admin', 'root@example.test');
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Student',
            'email' => 'new-student@example.test',
            'password' => 'StrongPassword!2026',
            'preferred_locale' => 'hi',
            'roles' => ['student'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new-student@example.test', 'is_active' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $admin = $this->makeUserWithRole('admin', 'admin-role@example.test');
        $permission = Permission::create(['name' => 'Manage users', 'slug' => 'users.manage']);
        $admin->roles()->first()->permissions()->attach($permission);
        Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Escalation Attempt',
            'email' => 'escalation@example.test',
            'password' => 'StrongPassword!2026',
            'preferred_locale' => 'en',
            'roles' => ['super-admin'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.test']);
    }

    public function test_super_admin_can_deactivate_user_and_revoke_api_tokens(): void
    {
        $admin = $this->makeUserWithRole('super-admin', 'root-status@example.test');
        $student = $this->makeUserWithRole('student', 'disable-me@example.test');
        $student->createToken('test-device');

        $this->actingAs($admin)->patch('/admin/users/'.$student->id.'/status')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => 0]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $student->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'subject_user_id' => $student->id]);
    }

    public function test_super_admin_cannot_deactivate_self_or_system_account(): void
    {
        $admin = $this->makeUserWithRole('super-admin', 'root-protected@example.test');

        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/status')->assertStatus(422);
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $student = $this->makeUserWithRole('student', 'inactive@example.test');
        $student->forceFill(['is_active' => false])->save();

        $this->post('/student/login', [
            'email' => 'inactive@example.test',
            'password' => 'StrongPassword!2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_admin_can_view_roles_permissions_and_audit_pages(): void
    {
        $admin = $this->makeUserWithRole('super-admin', 'root-pages@example.test');

        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->get('/admin/permissions')->assertOk();
        $this->actingAs($admin)->get('/admin/activity')->assertOk();
        $this->actingAs($admin)->get('/admin/profile')->assertOk();
    }
}
