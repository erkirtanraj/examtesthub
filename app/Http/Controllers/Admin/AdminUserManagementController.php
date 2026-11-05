<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserManagementController extends Controller
{
    private function audit(Request $request, string $action, ?User $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'subject_user_id' => $subject?->id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function users(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $query = User::query()->with('roles:id,name,slug')->withCount('roles')->latest('id');
        $query->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")));
        $query->when($filters['role'] ?? null, fn ($q, $slug) => $q->whereHas('roles', fn ($r) => $r->where('slug', $slug)));
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'));

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate($filters['per_page'] ?? 25)->withQueryString(),
            'roles' => Role::orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $filters,
        ]);
    }

    public function showUser(User $user): Response
    {
        $user->load('roles:id,name,slug');
        return Inertia::render('Admin/Users/Show', ['user' => $user->only('id', 'name', 'email', 'preferred_locale', 'is_active', 'deactivated_at', 'created_at'), 'roles' => $user->roles]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,slug'],
            'preferred_locale' => ['required', Rule::in(['en', 'hi'])],
        ]);
        $this->assertAssignableRoles($request, $data['roles']);
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => Str::lower($data['email']),
                'password' => Hash::make($data['password']), 'preferred_locale' => $data['preferred_locale'],
                'is_active' => true,
            ]);
            $user->roles()->sync(Role::whereIn('slug', $data['roles'])->pluck('id'));
            return $user;
        });
        $this->audit($request, 'user.created', $user, ['roles' => $data['roles']]);

        return back()->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_if(($user->hasRole('super-admin') || $user->hasRole('admin')) && ! $request->user()->hasRole('super-admin'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'preferred_locale' => ['required', Rule::in(['en', 'hi'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,slug'],
        ]);
        $this->assertAssignableRoles($request, $data['roles']);
        abort_if($user->hasRole('super-admin') && ! in_array('super-admin', $data['roles'], true), 422, 'The Super Admin role cannot be removed here.');
        $user->update(collect($data)->only(['name', 'email', 'preferred_locale'])->all());
        $user->roles()->sync(Role::whereIn('slug', $data['roles'])->pluck('id'));
        $this->audit($request, 'user.updated', $user, ['roles' => $data['roles']]);

        return back()->with('success', 'User updated successfully.');
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot deactivate your own account.');
        abort_if($user->hasRole('super-admin'), 403, 'The Super Admin account cannot be deactivated.');
        $user->forceFill([
            'is_active' => ! $user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
        ])->save();
        if (! $user->is_active) {
            $user->tokens()->delete();
        }
        $this->audit($request, $user->is_active ? 'user.activated' : 'user.deactivated', $user);

        return back()->with('success', 'User status updated.');
    }

    public function roles(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::with('permissions:id,name,slug')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:roles,slug'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
        $role = Role::create(collect($data)->only(['name', 'slug'])->all());
        $role->permissions()->sync($data['permissions'] ?? []);
        $this->audit($request, 'role.created', null, ['role_id' => $role->id, 'slug' => $role->slug]);

        return back()->with('success', 'Role created.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->slug === 'super-admin', 403, 'The Super Admin role is protected.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
        $role->update(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);
        $this->audit($request, 'role.permissions_updated', null, ['role_id' => $role->id]);

        return back()->with('success', 'Role permissions updated.');
    }

    public function permissions(): Response
    {
        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::withCount('roles')->orderBy('name')->get(),
        ]);
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'regex:/^[a-z0-9]+([.][a-z0-9-]+)+$/', 'max:120', 'unique:permissions,slug'],
        ]);
        $permission = Permission::create($data);
        $this->audit($request, 'permission.created', null, ['permission_id' => $permission->id, 'slug' => $permission->slug]);

        return back()->with('success', 'Permission created.');
    }

    public function profile(): Response
    {
        return Inertia::render('Admin/Profile/Index', [
            'user' => request()->user()->only('id', 'name', 'email', 'preferred_locale'),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'preferred_locale' => ['required', Rule::in(['en', 'hi'])],
        ]);
        $user->update($data);
        $this->audit($request, 'profile.updated', $user);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->delete();
        $this->audit($request, 'profile.password_changed', $request->user());
        $request->session()->regenerate();

        return back()->with('success', 'Password changed. Other API tokens were revoked.');
    }

    public function activity(): Response
    {
        return Inertia::render('Admin/Activity/Index', [
            'logs' => AuditLog::with(['actor:id,name,email', 'subject:id,name,email'])->latest()->paginate(50),
        ]);
    }

    public function exportUsers(): StreamedResponse
    {
        $filename = 'examtesthub-users-'.now()->format('Ymd-His').'.csv';
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'roles', 'status', 'preferred_locale', 'created_at']);
            User::with('roles')->orderBy('id')->chunk(500, function ($users) use ($out) {
                foreach ($users as $user) {
                    fputcsv($out, [$user->id, $user->name, $user->email, $user->roles->pluck('slug')->implode('|'), $user->is_active ? 'active' : 'inactive', $user->preferred_locale, $user->created_at?->toISOString()]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        abort_unless($handle, 422, 'Unable to read uploaded CSV.');
        $header = fgetcsv($handle);
        $expected = ['name', 'email', 'password', 'role', 'preferred_locale'];
        abort_unless($header && array_map('strtolower', $header) === $expected, 422, 'CSV headers must be: name,email,password,role,preferred_locale.');
        $created = 0;
        $errors = [];
        $line = 1;
        DB::transaction(function () use ($handle, &$created, &$errors, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) !== 5) { $errors[] = "Line {$line}: expected 5 columns."; continue; }
                [$name, $email, $password, $roleSlug, $locale] = array_map('trim', $row);
                $validator = validator(compact('name', 'email', 'password', 'locale'), [
                    'name' => ['required', 'string', 'max:120'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
                    'locale' => ['required', Rule::in(['en', 'hi'])],
                ]);
                if ($validator->fails() || ! Role::where('slug', $roleSlug)->exists() || ! in_array($roleSlug, ['student', 'content-manager', 'exam-manager'], true)) {
                    $errors[] = "Line {$line}: invalid fields, duplicate email, or disallowed role.";
                    continue;
                }
                $user = User::create(['name' => $name, 'email' => Str::lower($email), 'password' => Hash::make($password), 'preferred_locale' => $locale, 'is_active' => true]);
                $user->assignRole($roleSlug);
                $created++;
            }
        });
        fclose($handle);
        $this->audit($request, 'users.imported', null, ['created' => $created, 'rejected_rows' => count($errors)]);
        return back()->with('success', "{$created} users imported. ".count($errors)." rows rejected.")->with('import_errors', array_slice($errors, 0, 100));
    }

    private function assertAssignableRoles(Request $request, array $slugs): void
    {
        if (! $request->user()->hasRole('super-admin')) {
            $safeRoles = ['student', 'content-manager', 'exam-manager'];
            abort_if(array_diff($slugs, $safeRoles) !== [], 403, 'Only a Super Admin may assign privileged or custom roles.');
        }
    }
}
