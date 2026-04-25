<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BeneficiaryProfile;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\Relationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryFamilyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_beneficiary_profile_family_can_be_looked_up_for_the_client(): void
    {
        Relationship::create(['id' => 1, 'name' => 'Self']);
        $mother = Relationship::create(['name' => 'Mother']);
        $sibling = Relationship::create(['name' => 'Sibling']);

        $user = User::factory()->create(['role' => 'client']);
        $client = Client::create([
            'user_id' => $user->id,
            'last_name' => 'Client',
            'first_name' => 'Owner',
        ]);

        $beneficiaryProfile = BeneficiaryProfile::create([
            'client_id' => $client->id,
            'relationship_id' => $mother->id,
            'last_name' => 'Reyes',
            'first_name' => 'Ana',
            'middle_name' => 'Lopez',
            'birthdate' => '1985-02-10',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'beneficiary_profile_id' => $beneficiaryProfile->id,
            'user_id' => $user->id,
            'reference_no' => 'APP-2026-04-000002',
            'status' => 'submitted',
        ]);

        FamilyMember::create([
            'application_id' => $application->id,
            'client_id' => $client->id,
            'beneficiary_profile_id' => $beneficiaryProfile->id,
            'last_name' => 'Reyes',
            'first_name' => 'Paolo',
            'relationship' => (string) $sibling->id,
            'birthdate' => '2002-01-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('client.beneficiary-profile.lookup'), [
                'last_name' => 'Reyes',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'extension_name' => null,
                'birthdate' => '1985-02-10',
            ]);

        $response->assertOk()
            ->assertJsonPath('profile.id', $beneficiaryProfile->id)
            ->assertJsonPath('family.0.first_name', 'Paolo');
    }
}
