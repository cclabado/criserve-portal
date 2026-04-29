<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_assistance_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id');
            $table->foreignId('assistance_type_id');
            $table->foreignId('assistance_subtype_id')->nullable();
            $table->foreignId('assistance_detail_id')->nullable();
            $table->foreignId('mode_of_assistance_id')->nullable();
            $table->foreignId('frequency_rule_id')->nullable();
            $table->foreignId('frequency_basis_application_id')->nullable();
            $table->decimal('recommended_amount', 12, 2)->nullable();
            $table->decimal('final_amount', 12, 2);
            $table->string('frequency_status')->nullable();
            $table->text('frequency_message')->nullable();
            $table->string('frequency_case_key')->nullable();
            $table->text('frequency_override_reason')->nullable();
            $table->timestamp('frequency_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('application_id', 'aar_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('assistance_type_id', 'aar_type_fk')->references('id')->on('assistance_types')->cascadeOnDelete();
            $table->foreign('assistance_subtype_id', 'aar_subtype_fk')->references('id')->on('assistance_subtypes')->nullOnDelete();
            $table->foreign('assistance_detail_id', 'aar_detail_fk')->references('id')->on('assistance_details')->nullOnDelete();
            $table->foreign('mode_of_assistance_id', 'aar_mode_fk')->references('id')->on('mode_of_assistances')->nullOnDelete();
            $table->foreign('frequency_rule_id', 'aar_frequency_rule_fk')->references('id')->on('assistance_frequency_rules')->nullOnDelete();
            $table->foreign('frequency_basis_application_id', 'aar_frequency_basis_fk')->references('id')->on('applications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_assistance_recommendations');
    }
};
