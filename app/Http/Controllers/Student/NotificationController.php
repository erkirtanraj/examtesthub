<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20)->through(fn ($notification) => [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'Notification',
            'message' => $notification->data['message'] ?? '',
            'url' => $notification->data['url'] ?? '/student/notifications',
            'category' => $notification->data['category'] ?? 'general',
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Student/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'preferences' => array_merge(['email' => false, 'announcements' => true, 'exam_results' => true, 'course_updates' => true], $request->user()->notification_preferences ?? []),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return back(303);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back(303);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'boolean'],
            'announcements' => ['required', 'boolean'],
            'exam_results' => ['required', 'boolean'],
            'course_updates' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill(['notification_preferences' => $data])->save();

        return back(303)->with('success', 'Notification preferences updated.');
    }
}
