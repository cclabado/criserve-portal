<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_batches', function (Blueprint $table) {
            $table->decimal('payout_amount', 12, 2)->default(0)->after('venue');
        });

        Schema::table('payout_entries', function (Blueprint $table) {
            $table->string('proof_photo_disk')->nullable()->after('paid_by_user_id');
            $table->string('proof_photo_path')->nullable()->after('proof_photo_disk');
            $table->string('proof_photo_mime_type')->nullable()->after('proof_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('payout_entries', function (Blueprint $table) {
            $table->dropColumn([
                'proof_photo_disk',
                'proof_photo_path',
                'proof_photo_mime_type',
            ]);
        });

        Schema::table('payout_batches', function (Blueprint $table) {
            $table->dropColumn('payout_amount');
        });
    }
};
