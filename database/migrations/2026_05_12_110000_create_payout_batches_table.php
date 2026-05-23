<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bulk_deduplication_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('access_role');
            $table->string('batch_name');
            $table->string('sector_label');
            $table->string('venue');
            $table->date('payout_date')->nullable();
            $table->string('source_filename');
            $table->string('upload_disk');
            $table->string('upload_path');
            $table->json('summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['access_role', 'created_at']);
            $table->index(['payout_date', 'sector_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_batches');
    }
};
