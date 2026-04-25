<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_frequency_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_subtype_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistance_detail_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_type');
            $table->unsignedInteger('interval_months')->nullable();
            $table->boolean('requires_reference_date')->default(false);
            $table->boolean('requires_case_key')->default(false);
            $table->boolean('allows_exception_request')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_frequency_rules');
    }
};
