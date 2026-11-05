<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortalAuthenticationController extends Controller
{
    public function adminLoginPage(): Response|RedirectResponse
    {
        if (auth()->check()) {
            return auth()->user()->hasAnyRole(['super-admin', 'admin', 'content-manager', 'exam-manager'])
                ? redirect()->route('admin.dashboard')
                : redirect()->route('student.dashboard');
        }

        return Inertia::render('Auth/AdminLogin');
    }

    public function studentLoginPage(): Response|RedirectResponse
    {
        if (auth()->check()) {
            return auth()->user()->hasRole('student')
                ? redirect()->route('student.dashboard')
                : redirect()->route('admin.dashboard');
        }

        return Inertia::render('Auth/StudentLogin');
    }

    public function registerPage(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->loginToPortal(
            $request,
            ['super-admin', 'admin', 'content-manager', 'exam-manager'],
            'admin.dashboard'
        );
    }

    public function studentLogin(Request $request): RedirectResponse
    {
        return $this->loginToPortal($request, ['student'], 'student.dashboard');
    }

    private function loginToPortal(Request $request, array $allowedRoles, string $destination): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password) || ! $user->hasAnyRole($allowedRoles)) {
            throw ValidationException::withMessages([
                'email' => 'The supplied credentials are invalid for this portal.',
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($key);

        return redirect()->intended(route($destination));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            'preferred_locale' => ['nullable', 'in:en,hi'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'preferred_locale' => $data['preferred_locale'] ?? 'en',
        ]);

        $user->assignRole('student');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('locale', $user->preferred_locale);

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
