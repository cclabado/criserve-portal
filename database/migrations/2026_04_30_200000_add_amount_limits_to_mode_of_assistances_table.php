<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mode_of_assistances', function (Blueprint $table) {
            $table->decimal('minimum_amount', 12, 2)->nullable()->after('name');
            $table->decimal('maximum_amount', 12, 2)->nullable()->after('minimum_amount');
        });

        DB::table('mode_of_assistances')
            ->whereRaw('LOWER(name) = ?', ['cash'])
            ->update([
                'minimum_amount' => 0,
                'maximum_amount' => 10000,
            ]);

        DB::table('mode_of_assistances')
            ->whereRaw('LOWER(name) = ?', ['guarantee letter'])
            ->update([
                'minimum_amount' => 1,
                'maximum_amount' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('mode_of_assistances', function (Blueprint $table) {
            $table->dropColumn(['minimum_amount', 'maximum_amount']);
        });
    }
};
