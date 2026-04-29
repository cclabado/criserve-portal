<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gis_client_type')->nullable()->after('meeting_link');
            $table->string('gis_visit_type')->nullable()->after('gis_client_type');
            $table->string('diagnosis_or_cause_of_death')->nullable()->after('gis_visit_type');
            $table->string('occupation_sources')->nullable()->after('diagnosis_or_cause_of_death');
            $table->string('insurance_coverage')->nullable()->after('occupation_sources');
            $table->string('emergency_fund')->nullable()->after('insurance_coverage');
            $table->string('disease_duration')->nullable()->after('emergency_fund');
            $table->boolean('experienced_recent_crisis')->nullable()->after('disease_duration');
            $table->json('recent_crisis_types')->nullable()->after('experienced_recent_crisis');
            $table->json('support_systems')->nullable()->after('recent_crisis_types');
            $table->json('external_resources')->nullable()->after('support_systems');
            $table->json('self_help_efforts')->nullable()->after('external_resources');
            $table->string('client_sector')->nullable()->after('self_help_efforts');
            $table->string('client_sub_category')->nullable()->after('client_sector');
            $table->string('disability_type')->nullable()->after('client_sub_category');
            $table->decimal('total_income_past_six_months', 12, 2)->nullable()->after('disability_type');
            $table->json('income_sources')->nullable()->after('total_income_past_six_months');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'gis_client_type',
                'gis_visit_type',
                'diagnosis_or_cause_of_death',
                'occupation_sources',
                'insurance_coverage',
                'emergency_fund',
                'disease_duration',
                'experienced_recent_crisis',
                'recent_crisis_types',
                'support_systems',
                'external_resources',
                'self_help_efforts',
                'client_sector',
                'client_sub_category',
                'disability_type',
                'total_income_past_six_months',
                'income_sources',
            ]);
        });
    }
};
