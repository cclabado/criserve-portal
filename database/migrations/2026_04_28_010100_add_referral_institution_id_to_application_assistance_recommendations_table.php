<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->foreignId('referral_institution_id')
                ->nullable()
                ->after('mode_of_assistance_id');

            $table->foreign('referral_institution_id', 'aar_referral_institution_fk')
                ->references('id')
                ->on('referral_institutions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropForeign('aar_referral_institution_fk');
            $table->dropColumn('referral_institution_id');
        });
    }
};
