<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissionSlugs = [
            'users.view', 'users.manage',
            'courses.view', 'courses.manage', 'lessons.manage', 'materials.manage',
            'questions.manage', 'tests.manage', 'results.view', 'results.view-own',
            'tests.take', 'payments.view', 'payments.manage', 'settings.manage',
            'profile.manage', 'reports.view',
        ];

        $permissions = collect($permissionSlugs)->mapWithKeys(function (string $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => Str::of($slug)->replace('.', ' ')->headline()->toString()]
            );

            return [$slug => $permission->id];
        });

        $rolePermissions = [
            'super-admin' => $permissionSlugs,
            'admin' => ['users.view', 'users.manage', 'courses.view', 'courses.manage', 'lessons.manage', 'materials.manage', 'questions.manage', 'tests.manage', 'results.view', 'payments.view', 'payments.manage', 'settings.manage', 'reports.view'],
            'content-manager' => ['courses.view', 'courses.manage', 'lessons.manage', 'materials.manage'],
            'exam-manager' => ['questions.manage', 'tests.manage', 'results.view', 'reports.view'],
            'student' => ['courses.view', 'tests.take', 'results.view-own', 'profile.manage'],
        ];

        foreach ($rolePermissions as $slug => $slugs) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => Str::of($slug)->replace('-', ' ')->title()->toString()]
            );

            $role->permissions()->sync(collect($slugs)->map(fn ($permission) => $permissions[$permission])->all());
        }

        $email = config('examtesthub.super_admin.email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = config('examtesthub.super_admin.password');

            if (! is_string($password) || strlen($password) < 16) {
                throw new RuntimeException('Set SUPER_ADMIN_PASSWORD in .env (minimum 16 characters) before running db:seed.');
            }

            $user = User::create([
                'name' => config('examtesthub.super_admin.name'),
                'email' => $email,
                'password' => Hash::make($password),
                'preferred_locale' => 'en',
            ]);
        }

        $user->assignRole('super-admin');
    }
}
