<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

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

    public function test_social_worker_can_export_filtered_my_cases_to_excel(): void
    {
        $socialWorker = User::factory()->create(['role' => 'social_worker']);
        $otherSocialWorker = User::factory()->create(['role' => 'social_worker']);
        $clientUser = User::factory()->create(['role' => 'client']);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => null,
            'extension_name' => null,
            'contact_number' => '09123456789',
            'birthdate' => '1994-02-10',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'full_address' => 'Sample Address',
        ]);

        $matchingCase = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $socialWorker->id,
            'reference_no' => 'REF-APPROVED-001',
            'status' => 'approved',
        ]);
        $matchingCase->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $filteredOutByStatus = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $socialWorker->id,
            'reference_no' => 'REF-PENDING-002',
            'status' => 'under_review',
        ]);
        $filteredOutByStatus->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $filteredOutByDate = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $socialWorker->id,
            'reference_no' => 'REF-OLD-004',
            'status' => 'approved',
        ]);
        $filteredOutByDate->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->saveQuietly();

        $filteredOutByOwner = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $otherSocialWorker->id,
            'reference_no' => 'REF-OTHER-003',
            'status' => 'approved',
        ]);
        $filteredOutByOwner->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->saveQuietly();

        $response = $this->actingAs($socialWorker)->get(route('socialworker.my-cases', [
            'status' => 'approved',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $response->streamedContent();
        $tempPath = tempnam(sys_get_temp_dir(), 'my-cases-export-');
        file_put_contents($tempPath, $content);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempPath) === true);

        $sharedStrings = (string) $zip->getFromName('xl/sharedStrings.xml');

        $zip->close();
        @unlink($tempPath);

        $this->assertStringContainsString($matchingCase->reference_no, $sharedStrings);
        $this->assertStringNotContainsString($filteredOutByStatus->reference_no, $sharedStrings);
        $this->assertStringNotContainsString($filteredOutByDate->reference_no, $sharedStrings);
        $this->assertStringNotContainsString($filteredOutByOwner->reference_no, $sharedStrings);
    }
}
