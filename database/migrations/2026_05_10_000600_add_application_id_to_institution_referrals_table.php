<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_referrals', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->after('referred_by_user_id')->constrained()->nullOnDelete();
            $table->index(['application_id', 'status'], 'inst_ref_app_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('institution_referrals', function (Blueprint $table) {
            $table->dropIndex('inst_ref_app_status_idx');
            $table->dropConstrainedForeignId('application_id');
        });
    }
};
