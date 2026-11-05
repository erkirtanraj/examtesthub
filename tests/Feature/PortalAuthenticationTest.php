<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_is_assigned_student_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => 'StrongPassword!2026',
            'password_confirmation' => 'StrongPassword!2026',
            'preferred_locale' => 'hi',
        ]);

        $response->assertRedirect('/student/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'preferred_locale' => 'hi',
        ]);
        $this->assertTrue(auth()->user()->hasRole('student'));
    }

    public function test_student_cannot_sign_in_through_admin_portal(): void
    {
        $studentRole = Role::create(['name' => 'Student', 'slug' => 'student']);
        $student = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => Hash::make('StrongPassword!2026'),
        ]);
        $student->roles()->attach($studentRole);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'student@example.com',
            'password' => 'StrongPassword!2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_role_can_access_admin_dashboard_but_not_student_dashboard(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('StrongPassword!2026'),
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/student/dashboard')->assertForbidden();
    }

    public function test_student_api_token_can_be_issued_and_used(): void
    {
        $role = Role::create(['name' => 'Student', 'slug' => 'student']);
        $user = User::create([
            'name' => 'API Student',
            'email' => 'api-student@example.com',
            'password' => Hash::make('StrongPassword!2026'),
        ]);
        $user->roles()->attach($role);

        $tokenResponse = $this->postJson('/api/v1/auth/token', [
            'email' => 'api-student@example.com',
            'password' => 'StrongPassword!2026',
            'device_name' => 'test-device',
        ]);

        $tokenResponse->assertOk()->assertJsonPath('token_type', 'Bearer');
        $token = $tokenResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'api-student@example.com');
    }
}
