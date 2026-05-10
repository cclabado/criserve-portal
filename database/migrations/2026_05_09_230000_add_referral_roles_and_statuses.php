<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referral_institution_id')
                ->nullable()
                ->after('service_provider_id')
                ->constrained('referral_institutions')
                ->nullOnDelete();
        });

        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->string('referral_status')->nullable()->after('referral_institution_id');
            $table->text('referral_notes')->nullable()->after('referral_status');
            $table->timestamp('referred_at')->nullable()->after('referral_notes');
            $table->timestamp('referral_responded_at')->nullable()->after('referred_at');
        });
    }

    public function down(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropColumn([
                'referral_status',
                'referral_notes',
                'referred_at',
                'referral_responded_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_institution_id');
        });
    }
};
