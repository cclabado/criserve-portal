<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->index(
                ['referral_institution_id', 'referral_status', 'referred_at'],
                'aar_ref_inst_status_referred_idx'
            );
            $table->index(
                ['referral_status', 'referred_at'],
                'aar_ref_status_referred_idx'
            );
            $table->index(
                ['application_id', 'referral_status'],
                'aar_app_ref_status_idx'
            );
        });

        Schema::table('institution_referrals', function (Blueprint $table) {
            $table->index(
                ['referral_institution_id', 'submitted_at'],
                'inst_ref_inst_submitted_idx'
            );
            $table->index(
                ['status', 'reviewed_at'],
                'inst_ref_status_reviewed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropIndex('aar_ref_inst_status_referred_idx');
            $table->dropIndex('aar_ref_status_referred_idx');
            $table->dropIndex('aar_app_ref_status_idx');
        });

        Schema::table('institution_referrals', function (Blueprint $table) {
            $table->dropIndex('inst_ref_inst_submitted_idx');
            $table->dropIndex('inst_ref_status_reviewed_idx');
        });
    }
};
