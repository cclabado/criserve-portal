<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientDocumentComplianceRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Application $application
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $flaggedDocuments = $this->flaggedDocuments();

        $mail = (new MailMessage)
            ->subject('Document Compliance Required - '.$this->application->reference_no)
            ->greeting('Hello '.($notifiable->first_name ?: $notifiable->name).',')
            ->line('Your application needs updated or corrected supporting documents before it can continue.')
            ->line('Reference No: '.$this->application->reference_no);

        if (filled($this->application->client_compliance_notes)) {
            $mail->line('Compliance instructions: '.$this->application->client_compliance_notes);
        }

        if ($flaggedDocuments !== []) {
            $mail->line('Please review the flagged attachments below:');

            foreach ($flaggedDocuments as $document) {
                $mail->line('- '.$document);
            }
        }

        return $mail
            ->action('Review Application', route('client.application.show', $this->application->id))
            ->line('Upload clear and correct replacement files so your social worker can continue the review.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document compliance required',
            'reference_no' => $this->application->reference_no,
            'application_id' => $this->application->id,
            'message' => 'Your application has been returned for document compliance. Review the remarks and upload corrected attachments.',
            'compliance_notes' => $this->application->client_compliance_notes,
            'flagged_documents' => $this->flaggedDocuments(),
            'route' => route('client.application.show', $this->application->id),
        ];
    }

    protected function flaggedDocuments(): array
    {
        return $this->application->documents
            ->where('requires_client_resubmission', true)
            ->map(function ($document) {
                $label = $document->document_type ?: 'Supporting Document';
                $remarks = trim((string) $document->remarks);

                return $remarks !== ''
                    ? $label.': '.$remarks
                    : $label;
            })
            ->values()
            ->all();
    }
}
