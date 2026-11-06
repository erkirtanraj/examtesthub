<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url = '/student/notifications',
    ) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
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
