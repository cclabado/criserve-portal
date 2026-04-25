<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use App\Notifications\InitialAssessmentScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialAssessmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_gets_database_notification_when_initial_assessment_is_saved(): void
    {
        $socialWorker = User::factory()->create([
            'role' => 'social_worker',
        ]);

        $clientUser = User::factory()->create([
            'role' => 'client',
            'first_name' => 'Juan',
        ]);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'contact_number' => '09123456789',
            'full_address' => 'Sample Address',
            'sex' => 'Male',
            'birthdate' => '1990-05-01',
            'civil_status' => 'Single',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $clientUser->id,
            'reference_no' => 'REF-2026-0007',
            'status' => 'submitted',
        ]);

        $response = $this
            ->actingAs($socialWorker)
            ->post(route('socialworker.assess.update', $application->id), [
                'client_last_name' => 'Dela Cruz',
                'client_first_name' => 'Juan',
                'client_middle_name' => 'Santos',
                'client_extension_name' => null,
                'client_contact_number' => '09123456789',
                'client_address' => 'Sample Address',
                'client_sex' => 'Male',
                'client_birthdate' => '1990-05-01',
                'client_civil_status' => 'Single',
                'notes' => 'Bring your documentary requirements.',
                'schedule_date' => '2026-05-01 14:30:00',
                'meeting_link' => 'https://meet.google.com/test-link',
            ]);

        $response->assertRedirect(route('socialworker.applications'));

        $clientUser->refresh();
        $notification = $clientUser->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(InitialAssessmentScheduledNotification::class, $notification->type);
        $this->assertSame('REF-2026-0007', $notification->data['reference_no']);
        $this->assertSame('https://meet.google.com/test-link', $notification->data['meeting_link']);
    }

    public function test_notification_email_contains_schedule_and_meeting_link(): void
    {
        $clientUser = User::factory()->create([
            'first_name' => 'Juan',
            'email' => 'juan@example.com',
        ]);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $clientUser->id,
            'reference_no' => 'REF-2026-0010',
            'schedule_date' => '2026-05-10 10:00:00',
            'meeting_link' => 'https://meet.google.com/scheduled-link',
        ]);

        $mailMessage = (new InitialAssessmentScheduledNotification($application))->toMail($clientUser);

        $this->assertStringContainsString('REF-2026-0010', $mailMessage->subject);
        $this->assertContains('Schedule: May 10, 2026 10:00 AM', $mailMessage->introLines);
        $this->assertContains('Meeting Link: https://meet.google.com/scheduled-link', $mailMessage->introLines);
    }
}
