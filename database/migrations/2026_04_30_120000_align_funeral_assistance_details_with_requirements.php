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

        $obsoleteDetail = DB::table('assistance_details')
            ->where('assistance_subtype_id', $funeralSubtype->id)
            ->where('name', 'Casualties during Disaster / Calamity')
            ->first();

        if (! $obsoleteDetail) {
            return;
        }

        DB::table('assistance_frequency_rules')
            ->where('assistance_detail_id', $obsoleteDetail->id)
            ->delete();

        DB::table('assistance_details')
            ->where('id', $obsoleteDetail->id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $funeralSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Funeral Assistance')
            ->first();

        if (! $funeralSubtype) {
            return;
        }

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $funeralSubtype->id)
            ->where('name', 'Casualties during Disaster / Calamity')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
