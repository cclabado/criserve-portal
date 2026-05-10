<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index(['status', 'created_at']);
            $table->index(['status', 'updated_at']);
            $table->index(['social_worker_id', 'status']);
            $table->index(['social_worker_id', 'schedule_date']);
            $table->index(['approving_officer_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['beneficiary_profile_id', 'status']);
            $table->index(['assistance_subtype_id', 'status']);
            $table->index(['assistance_detail_id', 'status']);
            $table->index(['service_provider_id', 'status']);
            $table->index(['mode_of_assistance_id', 'status']);
            $table->index('frequency_case_key');
        });

        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->index(['assistance_subtype_id', 'updated_at'], 'aar_subtype_updated_idx');
            $table->index(['assistance_detail_id', 'updated_at'], 'aar_detail_updated_idx');
            $table->index('frequency_case_key', 'aar_frequency_case_key_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('is_active');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('bulk_deduplication_runs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['status', 'updated_at']);
            $table->dropIndex(['social_worker_id', 'status']);
            $table->dropIndex(['social_worker_id', 'schedule_date']);
            $table->dropIndex(['approving_officer_id', 'status']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['beneficiary_profile_id', 'status']);
            $table->dropIndex(['assistance_subtype_id', 'status']);
            $table->dropIndex(['assistance_detail_id', 'status']);
            $table->dropIndex(['service_provider_id', 'status']);
            $table->dropIndex(['mode_of_assistance_id', 'status']);
            $table->dropIndex(['frequency_case_key']);
        });

        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropIndex('aar_subtype_updated_idx');
            $table->dropIndex('aar_detail_updated_idx');
            $table->dropIndex('aar_frequency_case_key_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('bulk_deduplication_runs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
