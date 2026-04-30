<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuaranteeLetterApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Application $application
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Approved Guarantee Letter - '.$this->application->reference_no)
            ->greeting('Hello '.($notifiable->first_name ?: $notifiable->name).',')
            ->line('A guarantee letter has been approved and assigned to your service provider account.')
            ->line('Reference No: '.$this->application->reference_no)
            ->line('Client: '.$this->application->client?->first_name.' '.$this->application->client?->last_name)
            ->action('Open Guarantee Letters', route('service-provider.dashboard'))
            ->line('Please upload the updated statement of account once it is available.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Approved guarantee letter',
            'reference_no' => $this->application->reference_no,
            'application_id' => $this->application->id,
            'message' => 'A guarantee letter for '.$this->application->reference_no.' is ready for your service provider account.',
            'route' => route('service-provider.dashboard'),
        ];
    }
}
