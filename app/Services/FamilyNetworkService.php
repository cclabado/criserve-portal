<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProfile;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\User;
use Illuminate\Support\Collection;

class FamilyNetworkService
{
    public function syncUser(User $user): ?Person
    {
        $person = $this->findOrCreatePerson([
            'last_name' => $user->last_name,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'extension_name' => $user->extension_name,
            'birthdate' => $user->birthdate,
            'sex' => $user->sex,
        ]);

        if (! $person) {
            return null;
        }

        if ($user->person_id !== $person->id) {
            $user->person_id = $person->id;
            $user->save();
        }

        return $person;
    }

    public function syncClient(Client $client): ?Person
    {
        $person = $this->findOrCreatePerson([
            'last_name' => $client->last_name,
            'first_name' => $client->first_name,
            'middle_name' => $client->middle_name,
            'extension_name' => $client->extension_name,
            'birthdate' => $client->birthdate,
            'sex' => $client->sex,
        ]);

        if (! $person) {
            return null;
        }

        if ($client->person_id !== $person->id) {
            $client->person_id = $person->id;
            $client->save();
        }

        if ($client->user) {
            $this->syncUser($client->user);
        }

        return $person;
    }

    public function syncBeneficiaryProfile(BeneficiaryProfile $profile): ?Person
    {
        $person = $this->findOrCreatePerson([
            'last_name' => $profile->last_name,
            'first_name' => $profile->first_name,
            'middle_name' => $profile->middle_name,
            'extension_name' => $profile->extension_name,
            'birthdate' => $profile->birthdate,
            'sex' => $profile->sex,
        ]);

        if (! $person) {
            return null;
        }

        if ($profile->person_id !== $person->id) {
            $profile->person_id = $person->id;
            $profile->save();
        }

        return $person;
    }

    public function syncBeneficiary(Beneficiary $beneficiary): ?Person
    {
        $person = $this->findOrCreatePerson([
            'last_name' => $beneficiary->last_name,
            'first_name' => $beneficiary->first_name,
            'middle_name' => $beneficiary->middle_name,
            'extension_name' => $beneficiary->extension_name,
            'birthdate' => $beneficiary->birthdate,
            'sex' => $beneficiary->sex,
        ]);

        if (! $person) {
            return null;
        }

        if ($beneficiary->person_id !== $person->id) {
            $beneficiary->person_id = $person->id;
            $beneficiary->save();
        }

        return $person;
    }

    public function syncFamilyMember(FamilyMember $member): ?Person
    {
        $person = $this->findOrCreatePerson([
            'last_name' => $member->last_name,
            'first_name' => $member->first_name,
            'middle_name' => $member->middle_name,
            'extension_name' => $member->extension_name,
            'birthdate' => $member->birthdate,
        ]);

        if (! $person) {
            return null;
        }

        if ($member->person_id !== $person->id) {
            $member->person_id = $person->id;
            $member->save();
        }

        return $person;
    }

    public function syncApplicationNetwork(Application $application): void
    {
        $application->loadMissing([
            'client.user',
            'beneficiary',
            'beneficiaryProfile',
            'familyMembers',
        ]);

        $anchorPerson = $application->usesBeneficiaryHousehold() && $application->beneficiaryProfile
            ? $this->syncBeneficiaryProfile($application->beneficiaryProfile)
            : ($application->client ? $this->syncClient($application->client) : null);

        if ($application->beneficiary) {
            $this->syncBeneficiary($application->beneficiary);
        }

        if (! $anchorPerson) {
            return;
        }

        foreach ($this->resolveHouseholdMembers($application) as $member) {
            $relatedPerson = $this->syncFamilyMember($member);

            if (! $relatedPerson || $relatedPerson->id === $anchorPerson->id) {
                continue;
            }

            PersonRelationship::updateOrCreate(
                [
                    'person_id' => $anchorPerson->id,
                    'related_person_id' => $relatedPerson->id,
                    'relationship_id' => $member->relationship ?: null,
                    'source_application_id' => $application->id,
                ],
                [
                    'is_confirmed' => true,
                ]
            );
        }
    }

    public function buildApplicationNetwork(Application $application): array
    {
        $application->loadMissing([
            'client.person.users',
            'beneficiary.person.users',
            'beneficiary.relationshipData',
            'beneficiaryProfile.person.users',
            'familyMembers.person.users',
            'familyMembers.relationshipData',
        ]);

        $anchorPerson = $application->usesBeneficiaryHousehold() && $application->beneficiaryProfile?->person
            ? $application->beneficiaryProfile->person
            : $application->client?->person;

        $nodes = collect();
        $edges = collect();

        if ($anchorPerson) {
            $nodes->put($anchorPerson->id, $this->formatNode($anchorPerson, $application->usesBeneficiaryHousehold() ? 'Beneficiary Household Root' : 'Client Household Root'));
        }

        foreach ($this->resolveHouseholdMembers($application) as $member) {
            if (! $member->person) {
                continue;
            }

            $nodes->put($member->person->id, $this->formatNode($member->person, 'Family Member'));

            if ($anchorPerson && $anchorPerson->id !== $member->person->id) {
                $edges->push([
                    'from' => $anchorPerson->id,
                    'to' => $member->person->id,
                    'label' => $member->relationshipData->name ?? $member->relationship ?? 'Related',
                ]);
            }
        }

        if ($application->beneficiary?->person) {
            $nodes->put($application->beneficiary->person->id, $this->formatNode($application->beneficiary->person, 'Beneficiary'));
        }

        return [
            'anchor' => $anchorPerson ? $this->formatNode($anchorPerson, $application->usesBeneficiaryHousehold() ? 'Beneficiary Household Root' : 'Client Household Root') : null,
            'nodes' => $nodes->values()->all(),
            'edges' => $edges->all(),
        ];
    }

    protected function findOrCreatePerson(array $identity): ?Person
    {
        if (blank($identity['last_name'] ?? null) || blank($identity['first_name'] ?? null)) {
            return null;
        }

        $query = Person::query()
            ->whereRaw('LOWER(last_name) = ?', [strtolower(trim((string) $identity['last_name']))])
            ->whereRaw('LOWER(first_name) = ?', [strtolower(trim((string) $identity['first_name']))])
            ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [strtolower(trim((string) ($identity['middle_name'] ?? '')))])
            ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [strtolower(trim((string) ($identity['extension_name'] ?? '')))]);

        if (filled($identity['birthdate'] ?? null)) {
            $query->whereDate('birthdate', $identity['birthdate']);
        } else {
            $query->whereNull('birthdate');
        }

        $person = $query->first();

        if ($person) {
            $person->fill([
                'sex' => $person->sex ?: ($identity['sex'] ?? null),
            ]);
            $person->save();

            return $person;
        }

        return Person::create([
            'last_name' => $identity['last_name'],
            'first_name' => $identity['first_name'],
            'middle_name' => $identity['middle_name'] ?? null,
            'extension_name' => $identity['extension_name'] ?? null,
            'birthdate' => $identity['birthdate'] ?? null,
            'sex' => $identity['sex'] ?? null,
        ]);
    }

    protected function resolveHouseholdMembers(Application $application): Collection
    {
        if ($application->usesBeneficiaryHousehold() && $application->beneficiaryProfile) {
            return $application->beneficiaryProfile
                ->familyMembers()
                ->with(['person.users', 'relationshipData'])
                ->orderBy('id')
                ->get();
        }

        return $application->client
            ? $application->client->familyMembers()
                ->whereNull('beneficiary_profile_id')
                ->with(['person.users', 'relationshipData'])
                ->orderBy('id')
                ->get()
            : collect();
    }

    protected function formatNode(Person $person, string $role): array
    {
        $linkedAccount = $person->users->firstWhere('role', 'client') ?? $person->users->first();

        return [
            'id' => $person->id,
            'name' => $person->displayName(),
            'birthdate' => $person->birthdate,
            'role' => $role,
            'has_account' => (bool) $linkedAccount,
            'account_email' => $linkedAccount?->email,
        ];
    }
}
