<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->string('reference_no')->nullable();
            $table->string('full_name');
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('extension_name')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sector_label')->nullable();
            $table->string('assistance_subtype')->nullable();
            $table->string('assistance_detail')->nullable();
            $table->string('payout_status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->text('payout_notes')->nullable();
            $table->json('raw_row')->nullable();
            $table->timestamps();

            $table->index(['payout_batch_id', 'sequence_no']);
            $table->index(['payout_batch_id', 'payout_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_entries');
    }
};
