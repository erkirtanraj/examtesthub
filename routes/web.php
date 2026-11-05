<?php

use App\Http\Controllers\Auth\PortalAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'hi'], true), 404);

    session(['locale' => $locale]);

    if (request()->user()) {
        request()->user()->forceFill(['preferred_locale' => $locale])->save();
    }

    return back();
})->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [PortalAuthenticationController::class, 'adminLoginPage'])->name('admin.login');
    Route::post('/admin/login', [PortalAuthenticationController::class, 'adminLogin'])->middleware('throttle:5,1')->name('admin.login.store');

    Route::get('/student/login', [PortalAuthenticationController::class, 'studentLoginPage'])->name('student.login');
    Route::post('/student/login', [PortalAuthenticationController::class, 'studentLogin'])->middleware('throttle:5,1')->name('student.login.store');

    Route::get('/register', [PortalAuthenticationController::class, 'registerPage'])->name('register');
    Route::post('/register', [PortalAuthenticationController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::post('/logout', [PortalAuthenticationController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:super-admin|admin|content-manager|exam-manager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
    });

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Student/Dashboard'))->name('dashboard');
    });
