<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformEmailNotification;
use App\Notifications\PlatformNotification;

class NotificationDispatcher
{
    public static function send(
        User $user,
        string $title,
        string $message,
        string $url = '/student/notifications',
        string $category = 'announcement',
        bool $forceEmail = false,
    ): void {
        $user->notify(new PlatformNotification($title, $message, $url, $category));

        $preferences = $user->notification_preferences ?? [];
        if (($forceEmail || ($preferences['email'] ?? false)) && filled($user->email)) {
            $user->notify(new PlatformEmailNotification($title, $message, $url));
        }
    }
}
