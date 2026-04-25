<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialWorkerMyCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_can_only_see_their_owned_cases(): void
    {
        $socialWorker = User::factory()->create(['role' => 'social_worker']);
        $otherSocialWorker = User::factory()->create(['role' => 'social_worker']);
        $clientUser = User::factory()->create(['role' => 'client']);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'middle_name' => null,
            'extension_name' => null,
            'contact_number' => '09123456789',
            'birthdate' => '1998-01-01',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'full_address' => 'Sample Address',
        ]);

        $mine = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $socialWorker->id,
            'reference_no' => 'REF-MINE',
            'status' => 'under_review',
        ]);

        $others = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $otherSocialWorker->id,
            'reference_no' => 'REF-OTHER',
            'status' => 'for_approval',
        ]);

        $response = $this->actingAs($socialWorker)->get(route('socialworker.my-cases'));

        $response->assertOk();
        $response->assertSee($mine->reference_no);
        $response->assertDontSee($others->reference_no);
    }
}
