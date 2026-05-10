<?php

namespace App\Notifications;

use App\Models\BulkDeduplicationRun;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkDeduplicationFinishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BulkDeduplicationRun $run
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $route = $this->routeForRole();
        $isCompleted = $this->run->status === 'completed';
        $subject = $isCompleted ? 'Bulk deduplication completed' : 'Bulk deduplication needs attention';
        $message = $isCompleted
            ? 'Your bulk deduplication run has finished and the output files are ready.'
            : 'Your bulk deduplication run did not finish successfully. Please review the run details.';

        return (new MailMessage)
            ->subject('CriServe '.$subject)
            ->greeting('Hello '.($notifiable->first_name ?: $notifiable->name).',')
            ->line('Run: '.$this->run->original_filename)
            ->line($message)
            ->action('Open Deduplication Run', $route)
            ->line($this->run->progress_message ?: 'You can return to the deduplication page to review the result.');
    }

    public function toArray(object $notifiable): array
    {
        $isCompleted = $this->run->status === 'completed';

        return [
            'title' => $isCompleted ? 'Bulk deduplication completed' : 'Bulk deduplication failed',
            'message' => $isCompleted
                ? 'Your run for '.$this->run->original_filename.' is ready to review.'
                : 'Your run for '.$this->run->original_filename.' needs attention.',
            'run_id' => $this->run->id,
            'status' => $this->run->status,
            'route' => $this->routeForRole(),
        ];
    }

    protected function routeForRole(): string
    {
        $routeName = $this->run->access_role === 'reporting_officer'
            ? 'reporting.deduplication.index'
            : 'admin.deduplication.index';

        return route($routeName, ['run' => $this->run->id]);
    }
}
