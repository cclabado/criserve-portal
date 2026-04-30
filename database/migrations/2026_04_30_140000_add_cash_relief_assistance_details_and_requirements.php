<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cashReliefSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Cash Relief Assistance')
            ->first();

        if (! $cashReliefSubtype) {
            return;
        }

        $detailNames = [
            'Fire Victims',
            'Rescued Clients',
            'Victims of Online Sexual Exploitation (Children)',
            'Locally Stranded Individuals (LSI)',
            'Other Incidents',
        ];

        foreach ($detailNames as $detailName) {
            DB::table('assistance_details')->updateOrInsert(
                [
                    'assistance_subtype_id' => $cashReliefSubtype->id,
                    'name' => $detailName,
                ],
                [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $detailIdsByName = DB::table('assistance_details')
            ->where('assistance_subtype_id', $cashReliefSubtype->id)
            ->pluck('id', 'name');

        $upsert = function (?int $detailId, string $name, string $description, bool $isRequired, int $sortOrder) use ($cashReliefSubtype) {
            DB::table('assistance_document_requirements')->updateOrInsert(
                [
                    'assistance_subtype_id' => $cashReliefSubtype->id,
                    'assistance_detail_id' => $detailId,
                    'name' => $name,
                ],
                [
                    'description' => $description,
                    'is_required' => $isRequired,
                    'applies_when_amount_exceeds' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        };

        $upsert(
            null,
            'Authorization Letter',
            'Upload only if someone else is acting on behalf of the client or beneficiary.',
            false,
            20
        );

        $upsert(
            null,
            'Additional Supporting Documents from Authorities',
            'Upload any applicable supporting document from authorities, such as a police report or blotter, AFP or PNP spot report, JAPIC certification, death certificate, DAFAC, or medico-legal certificate.',
            false,
            90
        );

        $addCommonIdRequirement = function (int $detailId) use ($upsert) {
            $upsert(
                $detailId,
                'Valid ID of the client or person to be interviewed',
                'Upload a valid government-issued or institution-issued ID for the client or for the person who will be interviewed.',
                true,
                10
            );
        };

        if (isset($detailIdsByName['Fire Victims'])) {
            $detailId = $detailIdsByName['Fire Victims'];
            $addCommonIdRequirement($detailId);
            $upsert(
                $detailId,
                'Police Report or Bureau of Fire Protection (BFP) Report',
                'Upload the police report or the Bureau of Fire Protection report for the fire incident.',
                true,
                30
            );
        }

        if (isset($detailIdsByName['Rescued Clients'])) {
            $detailId = $detailIdsByName['Rescued Clients'];
            $addCommonIdRequirement($detailId);
            $upsert(
                $detailId,
                'Certification from a Social Worker or Case Manager',
                'Upload the certification issued by the social worker or case manager.',
                true,
                30
            );
        }

        if (isset($detailIdsByName['Victims of Online Sexual Exploitation (Children)'])) {
            $detailId = $detailIdsByName['Victims of Online Sexual Exploitation (Children)'];
            $addCommonIdRequirement($detailId);
            $upsert(
                $detailId,
                'Police Blotter',
                'Upload the police blotter for the case.',
                true,
                30
            );
            $upsert(
                $detailId,
                'Certification from a Social Worker',
                'Upload the certification issued by a social worker.',
                true,
                40
            );
        }

        if (isset($detailIdsByName['Locally Stranded Individuals (LSI)'])) {
            $detailId = $detailIdsByName['Locally Stranded Individuals (LSI)'];
            $upsert(
                $detailId,
                'Valid ID or Alternative Proof of Identity',
                'Upload a valid ID of the client or person to be interviewed. If there is no valid ID, upload either a medical certificate or a travel authority issued by the Philippine National Police as proof of identity.',
                true,
                10
            );
        }

        if (isset($detailIdsByName['Other Incidents'])) {
            $detailId = $detailIdsByName['Other Incidents'];
            $addCommonIdRequirement($detailId);
            $upsert(
                $detailId,
                'Barangay Certificate of Residency / Certificate of Indigency / Certification that the client is in need of assistance',
                'Upload any one of the following: barangay certificate of residency, certificate of indigency, or certification that the client is in need of assistance.',
                true,
                30
            );
        }
    }

    public function down(): void
    {
    }
};
