<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Announcements/Index', [
            'studentCount' => User::whereHas('roles', fn ($query) => $query->where('slug', 'student'))->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:all,selected'],
            'student_ids' => ['required_if:audience,selected', 'array', 'max:500'],
            'student_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'send_email' => ['sometimes', 'boolean'],
        ]);

        $students = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', 'student'));

        if ($data['audience'] === 'selected') {
            $ids = $data['student_ids'] ?? [];
            $students->whereIn('id', $ids);
        }

        $url = '/student/notifications';
        $sent = 0;
        $students->select(['id', 'name', 'email', 'notification_preferences'])->chunkById(200, function ($batch) use ($data, $url, &$sent) {
            foreach ($batch as $student) {
                $preferences = $student->notification_preferences ?? [];
                if (($preferences['announcements'] ?? true) === false) {
                    continue;
                }
                if (!empty($data['send_email'])) {
                    $preferences['email'] = true;
                    $student->setAttribute('notification_preferences', $preferences);
                }
                $student->notify(new PlatformNotification($data['title'], $data['message'], $url, 'announcement'));
                $sent++;
            }
        });

        return back(303)->with('success', "Announcement sent to {$sent} student(s).");
    }
}
