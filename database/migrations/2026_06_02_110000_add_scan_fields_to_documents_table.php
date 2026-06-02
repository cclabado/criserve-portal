<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('scan_status', 32)->nullable()->after('file_hash');
            $table->text('scan_message')->nullable()->after('scan_status');
            $table->timestamp('scan_requested_at')->nullable()->after('scan_message');
            $table->timestamp('scanned_at')->nullable()->after('scan_requested_at');
            $table->index(['scan_status', 'created_at'], 'documents_scan_status_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_scan_status_created_at_idx');
            $table->dropColumn([
                'scan_status',
                'scan_message',
                'scan_requested_at',
                'scanned_at',
            ]);
        });
    }
};
