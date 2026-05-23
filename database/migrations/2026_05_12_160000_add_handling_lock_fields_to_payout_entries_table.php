<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_entries', function (Blueprint $table) {
            $table->foreignId('handling_by_user_id')->nullable()->after('paid_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('handling_started_at')->nullable()->after('handling_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('payout_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handling_by_user_id');
            $table->dropColumn('handling_started_at');
        });
    }
};
