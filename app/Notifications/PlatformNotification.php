<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url = '/student/notifications',
        public string $category = 'announcement',
    ) {}

    public function via(object $notifiable): array
    {
        $preferences = $notifiable->notification_preferences ?? [];
        $channels = ['database'];

        if (($preferences['email'] ?? false) && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->action('Open ExamTestHub', url($this->url))
            ->line('You can manage email notification preferences from your account.');
    }
}
