<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProfile;
use App\Models\FamilyMember;
use App\Models\Relationship;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RelationshipBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $relationshipIds = Relationship::query()
            ->whereIn('name', ['Child', 'Sibling', 'Son', 'Daughter', 'Brother', 'Sister'])
            ->pluck('id', 'name');

        $this->backfillFamilyMembers($relationshipIds);
        $this->backfillBeneficiaries($relationshipIds);
        $this->backfillBeneficiaryProfiles($relationshipIds);
    }

    protected function backfillFamilyMembers($relationshipIds): void
    {
        FamilyMember::query()
            ->with(['person', 'linkedUser'])
            ->whereIn('relationship', [
                $relationshipIds['Child'] ?? 0,
                $relationshipIds['Sibling'] ?? 0,
            ])
            ->get()
            ->each(function (FamilyMember $member) use ($relationshipIds) {
                $sex = $member->person?->sex ?? $member->linkedUser?->sex;
                $newRelationshipId = $this->specificRelationshipId($member->relationship, $sex, $relationshipIds);

                if ($newRelationshipId && (int) $newRelationshipId !== (int) $member->relationship) {
                    $member->update(['relationship' => $newRelationshipId]);
                }
            });
    }

    protected function backfillBeneficiaries($relationshipIds): void
    {
        Beneficiary::query()
            ->whereIn('relationship_id', [
                $relationshipIds['Child'] ?? 0,
                $relationshipIds['Sibling'] ?? 0,
            ])
            ->get()
            ->each(function (Beneficiary $beneficiary) use ($relationshipIds) {
                $newRelationshipId = $this->specificRelationshipId($beneficiary->relationship_id, $beneficiary->sex, $relationshipIds);

                if ($newRelationshipId && (int) $newRelationshipId !== (int) $beneficiary->relationship_id) {
                    $beneficiary->update(['relationship_id' => $newRelationshipId]);
                }
            });
    }

    protected function backfillBeneficiaryProfiles($relationshipIds): void
    {
        BeneficiaryProfile::query()
            ->whereIn('relationship_id', [
                $relationshipIds['Child'] ?? 0,
                $relationshipIds['Sibling'] ?? 0,
            ])
            ->get()
            ->each(function (BeneficiaryProfile $profile) use ($relationshipIds) {
                $newRelationshipId = $this->specificRelationshipId($profile->relationship_id, $profile->sex, $relationshipIds);

                if ($newRelationshipId && (int) $newRelationshipId !== (int) $profile->relationship_id) {
                    $profile->update(['relationship_id' => $newRelationshipId]);
                }
            });
    }

    protected function specificRelationshipId(?int $currentRelationshipId, ?string $sex, $relationshipIds): ?int
    {
        $sex = Str::lower(trim((string) $sex));

        if ($sex === '') {
            return null;
        }

        if ((int) $currentRelationshipId === (int) ($relationshipIds['Child'] ?? 0)) {
            return $sex === 'male'
                ? ($relationshipIds['Son'] ?? null)
                : ($relationshipIds['Daughter'] ?? null);
        }

        if ((int) $currentRelationshipId === (int) ($relationshipIds['Sibling'] ?? 0)) {
            return $sex === 'male'
                ? ($relationshipIds['Brother'] ?? null)
                : ($relationshipIds['Sister'] ?? null);
        }

        return null;
    }
}
