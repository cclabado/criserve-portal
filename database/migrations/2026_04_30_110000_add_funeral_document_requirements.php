<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $funeralSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Funeral Assistance')
            ->first();

        if (! $funeralSubtype) {
            return;
        }

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $funeralSubtype->id)
            ->where('name', 'Funeral Expenses')
            ->update(['name' => 'Funeral Bill Payment']);

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $funeralSubtype->id)
            ->where('name', 'Transfer of Cadaver')
            ->update(['name' => 'Transfer of Remains (Cadaver Transfer Assistance)']);

        $detailIdsByName = DB::table('assistance_details')
            ->where('assistance_subtype_id', $funeralSubtype->id)
            ->pluck('id', 'name');

        $upsert = function (?int $detailId, string $name, string $description, bool $isRequired, int $sortOrder) use ($funeralSubtype) {
            DB::table('assistance_document_requirements')->updateOrInsert(
                [
                    'assistance_subtype_id' => $funeralSubtype->id,
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
            'Valid ID of the client or person to be interviewed',
            'Upload a valid government-issued or institution-issued ID for the client or for the person who will be interviewed.',
            true,
            10
        );

        $upsert(
            null,
            'Authorization Letter',
            'Upload only if someone else is acting on behalf of the client or beneficiary.',
            false,
            20
        );

        $funeralBillPaymentDetailId = $detailIdsByName['Funeral Bill Payment'] ?? null;
        $transferOfRemainsDetailId = $detailIdsByName['Transfer of Remains (Cadaver Transfer Assistance)'] ?? null;

        foreach (array_filter([$funeralBillPaymentDetailId]) as $detailId) {
            $upsert(
                $detailId,
                'Death Certificate or Certification',
                'Issued by the City/Municipal Civil Registry Office, Hospital, Funeral Parlor, Tribal Chieftain, or Imam. Original or certified true copy only.',
                true,
                30
            );
            $upsert(
                $detailId,
                'Promissory Note / Certificate of Balance / Statement of Account',
                'Upload any one of the following: promissory note, certificate of balance, or statement of account for the funeral bill payment request.',
                true,
                40
            );
            $upsert(
                $detailId,
                'Funeral Contract',
                'Upload the funeral contract for the funeral bill payment request.',
                true,
                50
            );
        }

        foreach (array_filter([$transferOfRemainsDetailId]) as $detailId) {
            $upsert(
                $detailId,
                'Death Certificate or Certification',
                'Issued by the City/Municipal Civil Registry Office, Hospital, Funeral Parlor, Tribal Chieftain, or Imam. Original or certified true copy only.',
                true,
                30
            );
            $upsert(
                $detailId,
                'Transfer Permit',
                'Upload the transfer permit for the cadaver transfer assistance request.',
                true,
                40
            );
        }
    }

    public function down(): void
    {
    }
};
