<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_deduplication_runs', function (Blueprint $table) {
            $table->string('reference_upload_disk')->nullable()->after('upload_path');
            $table->string('reference_upload_path')->nullable()->after('reference_upload_disk');
            $table->string('status')->default('queued')->after('reference_upload_path');
            $table->unsignedTinyInteger('progress_percentage')->default(0)->after('status');
            $table->string('progress_message')->nullable()->after('progress_percentage');
            $table->text('error_message')->nullable()->after('progress_message');
            $table->timestamp('started_at')->nullable()->after('error_message');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('failed_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bulk_deduplication_runs', function (Blueprint $table) {
            $table->dropColumn([
                'reference_upload_disk',
                'reference_upload_path',
                'status',
                'progress_percentage',
                'progress_message',
                'error_message',
                'started_at',
                'completed_at',
                'failed_at',
            ]);
        });
    }
};
