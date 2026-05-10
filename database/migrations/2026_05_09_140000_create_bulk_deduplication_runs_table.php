<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_deduplication_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('access_role')->nullable();
            $table->string('original_filename');
            $table->string('upload_disk')->nullable();
            $table->string('upload_path')->nullable();
            $table->json('summary');
            $table->longText('clean_rows');
            $table->longText('duplicate_rows');
            $table->longText('finding_rows');
            $table->longText('skipped_rows');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_deduplication_runs');
    }
};
