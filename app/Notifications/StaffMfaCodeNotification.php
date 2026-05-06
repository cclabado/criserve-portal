<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffMfaCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $code,
        protected int $expiresInMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CriServe Security Code')
            ->line('A sign-in attempt for your CriServe staff account requires a verification code.')
            ->line('Your verification code is: '.$this->code)
            ->line('This code will expire in '.$this->expiresInMinutes.' minutes.')
            ->line('If you did not attempt to sign in, please change your password immediately and notify an administrator.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Security verification code',
            'message' => 'Use code '.$this->code.' to complete your CriServe sign-in.',
        ];
    }
}
