<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialWorkerGoogleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_can_connect_google_calendar(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect_uri', 'http://127.0.0.1/social-worker/google/callback');

        $socialWorker = User::factory()->create([
            'role' => 'social_worker',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'refresh_token' => 'google-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'worker@example.com',
            ]),
        ]);

        $response = $this
            ->actingAs($socialWorker)
            ->withSession(['google_oauth_state' => 'expected-state'])
            ->get(route('socialworker.google.callback', [
                'code' => 'sample-code',
                'state' => 'expected-state',
            ]));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'google-calendar-connected');

        $socialWorker->refresh();

        $this->assertSame('worker@example.com', $socialWorker->google_email);
        $this->assertTrue($socialWorker->hasGoogleCalendarConnection());
        $this->assertNotNull($socialWorker->google_calendar_connected_at);
    }

    public function test_assessment_schedule_creates_google_calendar_event_and_meet_link(): void
    {
        config()->set('services.google.calendar_base_url', 'https://www.googleapis.com/calendar/v3');
        config()->set('services.google.default_event_duration', 60);

        $socialWorker = User::factory()->create([
            'role' => 'social_worker',
            'google_access_token' => 'google-access-token',
            'google_refresh_token' => 'google-refresh-token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $clientUser = User::factory()->create([
            'role' => 'client',
        ]);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'contact_number' => '09123456789',
            'full_address' => 'Sample Address',
            'sex' => 'Female',
            'birthdate' => '1992-05-01',
            'civil_status' => 'Single',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $clientUser->id,
            'reference_no' => 'REF-2026-0001',
            'status' => 'submitted',
        ]);

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'id' => 'google-event-123',
                'htmlLink' => 'https://calendar.google.com/event?eid=123',
                'hangoutLink' => 'https://meet.google.com/abc-defg-hij',
            ]),
        ]);

        $response = $this
            ->actingAs($socialWorker)
            ->post(route('socialworker.assess.update', $application->id), [
                'client_last_name' => 'Dela Cruz',
                'client_first_name' => 'Maria',
                'client_middle_name' => 'Santos',
                'client_extension_name' => null,
                'client_contact_number' => '09123456789',
                'client_address' => 'Sample Address',
                'client_sex' => 'Female',
                'client_birthdate' => '1992-05-01',
                'client_civil_status' => 'Single',
                'notes' => 'Please discuss documentary requirements.',
                'schedule_date' => '2026-05-01 09:00:00',
            ]);

        $response->assertRedirect(route('socialworker.applications'));
        $response->assertSessionHas('success', 'Assessment saved successfully.');

        $application->refresh();

        $this->assertSame('under_review', $application->status);
        $this->assertSame('https://meet.google.com/abc-defg-hij', $application->meeting_link);
        $this->assertSame('google-event-123', $application->google_calendar_event_id);
        $this->assertSame('https://calendar.google.com/event?eid=123', $application->google_calendar_event_link);
    }
}
