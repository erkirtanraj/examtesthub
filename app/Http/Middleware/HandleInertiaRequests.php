<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => app()->getLocale(),\n            'unreadNotificationsCount' => $user ? $user->unreadNotifications()->count() : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'preferred_locale' => $user->preferred_locale,
                    'roles' => $user->roles()->pluck('slug'),
                    'permissions' => $user->hasRole('super-admin')
                        ? Permission::pluck('slug')
                        : $user->roles()->with('permissions')->get()->flatMap(fn ($role) => $role->permissions->pluck('slug'))->unique()->values(),
                ] : null,
            ],
        ];
    }
}
