<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url = '/student/notifications',
        public string $category = 'announcement',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
        ];
    }
}
