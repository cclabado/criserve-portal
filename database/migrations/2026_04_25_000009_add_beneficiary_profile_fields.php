<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('beneficiary_profile_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->foreignId('beneficiary_profile_id')->nullable()->after('client_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('beneficiary_profile_id')->nullable()->after('application_id')->constrained()->nullOnDelete();
        });

        DB::table('applications')
            ->whereNotNull('client_id')
            ->orderBy('id')
            ->get(['id', 'client_id'])
            ->each(function ($application) {
                DB::table('family_members')
                    ->where('application_id', $application->id)
                    ->whereNull('beneficiary_profile_id')
                    ->update([
                        'client_id' => $application->client_id,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('beneficiary_profile_id');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('beneficiary_profile_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('beneficiary_profile_id');
        });
    }
};
