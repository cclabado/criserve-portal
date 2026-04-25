<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\Application;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Relationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClientFamilyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_client_profile_and_family_are_reused_for_new_applications(): void
    {
        Relationship::create(['id' => 1, 'name' => 'Self']);
        $spouse = Relationship::create(['name' => 'Spouse']);
        $child = Relationship::create(['name' => 'Child']);

        $user = User::factory()->create([
            'role' => 'client',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'contact_number' => '09123456789',
            'full_address' => 'Old Address',
            'sex' => 'Male',
            'birthdate' => '1990-01-01',
            'civil_status' => 'Single',
        ]);

        $existingApplication = Application::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'reference_no' => 'APP-2026-04-000001',
            'status' => 'submitted',
        ]);

        $assistanceType = AssistanceType::create(['name' => 'Medical']);
        $assistanceSubtype = AssistanceSubtype::create([
            'assistance_type_id' => $assistanceType->id,
            'name' => 'Hospital Bill',
        ]);

        $familyMember = FamilyMember::create([
            'application_id' => $existingApplication->id,
            'client_id' => $client->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'relationship' => (string) $spouse->id,
            'birthdate' => '1991-02-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/client/application', [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'extension_name' => null,
                'contact_number' => '09999999999',
                'birthdate' => '1990-01-01',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'full_address' => 'New Address',
                'relationship_id' => 1,
                'assistance_type_id' => $assistanceType->id,
                'assistance_subtype_id' => $assistanceSubtype->id,
                'mode_of_assistance' => 'cash',
                'documents' => [UploadedFile::fake()->create('requirement.pdf', 100)],
                'family_id' => [$familyMember->id, null],
                'family_last_name' => ['Dela Cruz', 'Dela Cruz'],
                'family_first_name' => ['Maria', 'Junior'],
                'family_middle_name' => ['Santos', 'Santos'],
                'family_relationship' => [$spouse->id, $child->id],
                'family_birthdate' => ['1991-02-01', '2015-06-01'],
            ]);

        $response->assertRedirect('/client/dashboard');

        $this->assertSame(1, Client::count());

        $client->refresh();
        $this->assertSame('New Address', $client->full_address);
        $this->assertSame('09999999999', $client->contact_number);
        $this->assertSame('Married', $client->civil_status);

        $this->assertSame(2, $client->familyMembers()->count());
        $this->assertDatabaseHas('family_members', [
            'id' => $familyMember->id,
            'client_id' => $client->id,
            'first_name' => 'Maria',
        ]);
        $this->assertDatabaseHas('family_members', [
            'client_id' => $client->id,
            'first_name' => 'Junior',
            'relationship' => (string) $child->id,
        ]);

        $application = $client->applications()->latest('id')->first();
        $this->assertNotNull($application);
        $this->assertSame($client->id, $application->client_id);
        $this->assertCount(2, $application->fresh()->familyMembers);
    }
}
