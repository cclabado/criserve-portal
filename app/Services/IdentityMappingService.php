<?php

namespace App\Services;

use App\Models\Client;
use App\Models\BeneficiaryProfile;
use App\Models\FamilyMember;
use App\Models\User;

class IdentityMappingService
{
    public function __construct(
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    public function syncUserMappings(User $user): void
    {
        $this->familyNetwork->syncUser($user);

        $identity = $this->identityPayload(
            $user->last_name,
            $user->first_name,
            $user->middle_name,
            $user->extension_name,
            $user->birthdate,
        );

        BeneficiaryProfile::query()
            ->whereRaw('LOWER(last_name) = ?', [$identity['last_name']])
            ->whereRaw('LOWER(first_name) = ?', [$identity['first_name']])
            ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [$identity['middle_name']])
            ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [$identity['extension_name']])
            ->whereDate('birthdate', $identity['birthdate'])
            ->update(['linked_user_id' => $user->id]);

        FamilyMember::query()
            ->whereRaw('LOWER(last_name) = ?', [$identity['last_name']])
            ->whereRaw('LOWER(first_name) = ?', [$identity['first_name']])
            ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [$identity['middle_name']])
            ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [$identity['extension_name']])
            ->whereDate('birthdate', $identity['birthdate'])
            ->update(['linked_user_id' => $user->id]);
    }

    public function syncClientFamilyComposition(Client $client, User $user): void
    {
        if ($client->familyMembers()->whereNull('beneficiary_profile_id')->exists()) {
            return;
        }

        $beneficiaryProfile = BeneficiaryProfile::query()
            ->with('familyMembers')
            ->where('linked_user_id', $user->id)
            ->whereHas('familyMembers')
            ->latest('updated_at')
            ->first();

        if ($beneficiaryProfile) {
            $this->cloneFamilyRowsToClient($client, $beneficiaryProfile->familyMembers);
            return;
        }

        $matchedFamilyMember = FamilyMember::query()
            ->where('linked_user_id', $user->id)
            ->latest('updated_at')
            ->first();

        if (! $matchedFamilyMember) {
            return;
        }

        $householdMembers = $this->resolveRelatedHouseholdMembers($matchedFamilyMember, $user);

        if ($householdMembers->isEmpty()) {
            return;
        }

        $this->cloneFamilyRowsToClient($client, $householdMembers);
    }

    public function syncBeneficiaryProfile(BeneficiaryProfile $profile): void
    {
        $this->familyNetwork->syncBeneficiaryProfile($profile);

        $profile->linked_user_id = $this->findMatchingClientUserId(
            $profile->last_name,
            $profile->first_name,
            $profile->middle_name,
            $profile->extension_name,
            $profile->birthdate,
        );

        $profile->save();
    }

    public function syncFamilyMember(FamilyMember $member): void
    {
        $this->familyNetwork->syncFamilyMember($member);

        $member->linked_user_id = $this->findMatchingClientUserId(
            $member->last_name,
            $member->first_name,
            $member->middle_name,
            $member->extension_name,
            $member->birthdate,
        );

        $member->save();
    }

    protected function findMatchingClientUserId(
        ?string $lastName,
        ?string $firstName,
        ?string $middleName,
        ?string $extensionName,
        $birthdate
    ): ?int {
        if (blank($lastName) || blank($firstName) || blank($birthdate)) {
            return null;
        }

        $identity = $this->identityPayload($lastName, $firstName, $middleName, $extensionName, $birthdate);

        return User::query()
            ->where('role', 'client')
            ->whereRaw('LOWER(last_name) = ?', [$identity['last_name']])
            ->whereRaw('LOWER(first_name) = ?', [$identity['first_name']])
            ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [$identity['middle_name']])
            ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [$identity['extension_name']])
            ->whereDate('birthdate', $identity['birthdate'])
            ->value('id');
    }

    protected function identityPayload(
        ?string $lastName,
        ?string $firstName,
        ?string $middleName,
        ?string $extensionName,
        $birthdate
    ): array {
        return [
            'last_name' => strtolower(trim((string) $lastName)),
            'first_name' => strtolower(trim((string) $firstName)),
            'middle_name' => strtolower(trim((string) ($middleName ?? ''))),
            'extension_name' => strtolower(trim((string) ($extensionName ?? ''))),
            'birthdate' => $birthdate,
        ];
    }

    protected function resolveRelatedHouseholdMembers(FamilyMember $matchedFamilyMember, User $user)
    {
        if ($matchedFamilyMember->beneficiary_profile_id) {
            return FamilyMember::query()
                ->where('beneficiary_profile_id', $matchedFamilyMember->beneficiary_profile_id)
                ->whereKeyNot($matchedFamilyMember->id)
                ->orderBy('id')
                ->get();
        }

        return FamilyMember::query()
            ->where('client_id', $matchedFamilyMember->client_id)
            ->whereNull('beneficiary_profile_id')
            ->orderBy('id')
            ->get()
            ->reject(function (FamilyMember $member) use ($user) {
                return $this->isSameIdentity($member, $user);
            })
            ->values();
    }

    protected function cloneFamilyRowsToClient(Client $client, $familyMembers): void
    {
        $seen = [];

        foreach ($familyMembers as $member) {
            $signature = implode('|', [
                strtolower(trim((string) $member->last_name)),
                strtolower(trim((string) $member->first_name)),
                strtolower(trim((string) ($member->middle_name ?? ''))),
                strtolower(trim((string) ($member->extension_name ?? ''))),
                (string) $member->birthdate,
            ]);

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;

            $client->familyMembers()->create([
                'application_id' => $member->application_id,
                'beneficiary_profile_id' => null,
                'linked_user_id' => $member->linked_user_id,
                'person_id' => $member->person_id,
                'last_name' => $member->last_name,
                'first_name' => $member->first_name,
                'middle_name' => $member->middle_name,
                'extension_name' => $member->extension_name,
                'relationship' => $member->relationship,
                'birthdate' => $member->birthdate,
            ]);
        }
    }

    protected function isSameIdentity(FamilyMember $member, User $user): bool
    {
        return strtolower(trim((string) $member->last_name)) === strtolower(trim((string) $user->last_name))
            && strtolower(trim((string) $member->first_name)) === strtolower(trim((string) $user->first_name))
            && strtolower(trim((string) ($member->middle_name ?? ''))) === strtolower(trim((string) ($user->middle_name ?? '')))
            && strtolower(trim((string) ($member->extension_name ?? ''))) === strtolower(trim((string) ($user->extension_name ?? '')))
            && (string) $member->birthdate === (string) $user->birthdate;
    }
}
