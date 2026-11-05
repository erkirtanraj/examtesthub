<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StudentProfileSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentProfileSettingsController extends Controller
{
    public function index(): Response
    {
        $settings = StudentProfileSetting::current();

        return Inertia::render('Admin/Settings/StudentProfile', [
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
        $data = $request->validate([
            'allow_profile_updates' => ['required', 'boolean'],
            'allow_name_edit' => ['required', 'boolean'],
            'allow_email_edit' => ['required', 'boolean'],
            'allow_language_edit' => ['required', 'boolean'],
            'allow_password_change' => ['required', 'boolean'],
        ]);

        $settings = StudentProfileSetting::current();
        $settings->update($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'settings.student_profile_updated',
            'metadata' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500, ''),
        ]);

        return back()->with('success', 'Student profile settings updated.');
    }
}
