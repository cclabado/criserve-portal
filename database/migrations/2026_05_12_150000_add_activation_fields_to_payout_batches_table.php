<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_batches', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('notes');
            $table->foreignId('activated_by_user_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable()->after('activated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('payout_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activated_by_user_id');
            $table->dropColumn(['is_active', 'activated_at']);
        });
    }
};
