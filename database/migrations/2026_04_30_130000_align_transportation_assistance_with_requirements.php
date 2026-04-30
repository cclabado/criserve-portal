<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $transportationSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Transportation Assistance')
            ->first();

        if (! $transportationSubtype) {
            return;
        }

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $transportationSubtype->id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $upsert = function (string $name, string $description, bool $isRequired, int $sortOrder) use ($transportationSubtype) {
            DB::table('assistance_document_requirements')->updateOrInsert(
                [
                    'assistance_subtype_id' => $transportationSubtype->id,
                    'assistance_detail_id' => null,
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
            'Valid ID of the client or person to be interviewed',
            'Upload a valid government-issued or institution-issued ID for the client or for the person who will be interviewed.',
            true,
            10
        );

        $upsert(
            'Authorization Letter',
            'Upload only if someone else is acting on behalf of the client or beneficiary.',
            false,
            20
        );

        $upsert(
            'Supporting Documents',
            'Upload any applicable supporting document depending on the purpose of travel, such as a medical certificate, death certificate, court order, or subpoena.',
            true,
            30
        );
    }

    public function down(): void
    {
        $transportationSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Transportation Assistance')
            ->first();

        if (! $transportationSubtype) {
            return;
        }

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $transportationSubtype->id)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
