<?php

namespace App\Notifications;

use App\Models\Application;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InitialAssessmentScheduledNotification extends Notification
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
            ->subject('Initial Assessment Scheduled - '.$this->application->reference_no)
            ->greeting('Hello '.($notifiable->first_name ?: $notifiable->name).',')
            ->line('Your initial assessment has been scheduled for your assistance application.')
            ->line('Reference No: '.$this->application->reference_no)
            ->line('Schedule: '.$this->formattedSchedule())
            ->line('Meeting Link: '.($this->application->meeting_link ?: 'Will be shared by your social worker.'))
            ->action('View Application', route('client.application.show', $this->application->id))
            ->line('Please join on time and keep your supporting documents ready.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Initial assessment scheduled',
            'reference_no' => $this->application->reference_no,
            'application_id' => $this->application->id,
            'message' => 'Your initial assessment has been scheduled for '.$this->formattedSchedule().'.',
            'schedule' => $this->formattedSchedule(),
            'meeting_link' => $this->application->meeting_link,
            'route' => route('client.application.show', $this->application->id),
        ];
    }

    protected function formattedSchedule(): string
    {
        $schedule = $this->application->schedule_date;

        if ($schedule instanceof CarbonInterface) {
            return $schedule->timezone(config('app.timezone'))->format('M d, Y h:i A');
        }

        return 'To be announced';
    }
}
