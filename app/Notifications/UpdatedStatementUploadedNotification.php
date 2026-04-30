<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdatedStatementUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Application $application,
        protected string $fileName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Updated Statement Uploaded - '.$this->application->reference_no)
            ->greeting('Hello '.($notifiable->first_name ?: $notifiable->name).',')
            ->line('The assigned service provider uploaded an updated statement of account.')
            ->line('Reference No: '.$this->application->reference_no)
            ->line('File: '.$this->fileName)
            ->action('Review Application', route('socialworker.show', $this->application->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Updated statement uploaded',
            'reference_no' => $this->application->reference_no,
            'application_id' => $this->application->id,
            'message' => 'A service provider uploaded an updated statement of account for '.$this->application->reference_no.'.',
            'route' => route('socialworker.show', $this->application->id),
        ];
    }
}
