<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_batches', function (Blueprint $table) {
            $table->string('import_status')->default('queued')->after('upload_path');
            $table->unsignedInteger('processed_rows')->default(0)->after('import_status');
            $table->string('progress_message')->nullable()->after('processed_rows');
            $table->text('error_message')->nullable()->after('progress_message');
            $table->timestamp('import_started_at')->nullable()->after('error_message');
            $table->timestamp('import_completed_at')->nullable()->after('import_started_at');
            $table->timestamp('import_failed_at')->nullable()->after('import_completed_at');

            $table->index(['import_status', 'created_at'], 'payout_batches_import_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payout_batches', function (Blueprint $table) {
            $table->dropIndex('payout_batches_import_status_created_idx');
            $table->dropColumn([
                'import_status',
                'processed_rows',
                'progress_message',
                'error_message',
                'import_started_at',
                'import_completed_at',
                'import_failed_at',
            ]);
        });
    }
};
