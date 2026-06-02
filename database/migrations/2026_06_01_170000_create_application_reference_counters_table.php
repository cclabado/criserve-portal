<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_reference_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('reference_year');
            $table->unsignedTinyInteger('reference_month');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['reference_year', 'reference_month'], 'app_reference_counters_period_unique');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->unique('reference_no', 'applications_reference_no_unique');
            $table->index(
                ['client_id', 'beneficiary_profile_id', 'assistance_subtype_id', 'assistance_detail_id', 'status'],
                'app_duplicate_active_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_reference_no_unique');
            $table->dropIndex('app_duplicate_active_lookup_idx');
        });

        Schema::dropIfExists('application_reference_counters');
    }
};
