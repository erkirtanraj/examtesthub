<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StudentProfileSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class StudentProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = StudentProfileSetting::current();

        return Inertia::render('Student/Profile/Index', [
            'user' => $request->user()->only('id', 'name', 'email', 'preferred_locale'),
            'settings' => $settings->only([
                'allow_profile_updates',
                'allow_name_edit',
                'allow_email_edit',
                'allow_language_edit',
                'allow_password_change',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = StudentProfileSetting::current();
        abort_unless($settings->allow_profile_updates, 403, 'Student profile updates are currently disabled.');

        $rules = [];
        if ($settings->allow_name_edit) {
            $rules['name'] = ['required', 'string', 'max:120'];
        }
        if ($settings->allow_email_edit) {
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)];
        }
        if ($settings->allow_language_edit) {
            $rules['preferred_locale'] = ['required', Rule::in(['en', 'hi'])];
        }

        abort_if($rules === [], 403, 'No profile fields are currently editable.');

        $data = $request->validate($rules);
        $request->user()->update($data);

        if (isset($data['preferred_locale'])) {
            $request->session()->put('locale', $data['preferred_locale']);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'subject_user_id' => $request->user()->id,
            'action' => 'student.profile.updated',
            'metadata' => ['updated_fields' => array_keys($data)],
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500, ''),
        ]);

        return back()->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $settings = StudentProfileSetting::current();
        abort_unless($settings->allow_password_change, 403, 'Password changes are currently disabled.');

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->delete();
        $request->session()->regenerate();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'subject_user_id' => $request->user()->id,
            'action' => 'student.profile.password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500, ''),
        ]);

        return back()->with('success', 'Your password has been changed.');
    }
}
