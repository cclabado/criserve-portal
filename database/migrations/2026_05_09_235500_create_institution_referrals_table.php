<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_referrals')) {
            Schema::create('institution_referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referral_institution_id')->constrained()->cascadeOnDelete();
                $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject_type')->default('client');
                $table->string('client_last_name');
                $table->string('client_first_name');
                $table->string('client_middle_name')->nullable();
                $table->string('client_extension_name')->nullable();
                $table->date('client_birthdate')->nullable();
                $table->string('client_contact_number')->nullable();
                $table->text('client_address')->nullable();
                $table->string('beneficiary_last_name')->nullable();
                $table->string('beneficiary_first_name')->nullable();
                $table->string('beneficiary_middle_name')->nullable();
                $table->string('beneficiary_extension_name')->nullable();
                $table->date('beneficiary_birthdate')->nullable();
                $table->string('beneficiary_contact_number')->nullable();
                $table->text('beneficiary_address')->nullable();
                $table->string('requested_assistance')->nullable();
                $table->text('case_summary')->nullable();
                $table->text('institution_notes')->nullable();
                $table->text('officer_notes')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('institution_referrals', function (Blueprint $table) {
            $table->index(['referral_institution_id', 'status'], 'inst_ref_inst_status_idx');
            $table->index(['status', 'submitted_at'], 'inst_ref_status_submitted_idx');
            $table->index(['client_last_name', 'client_first_name', 'client_birthdate'], 'inst_ref_client_match_idx');
            $table->index(['beneficiary_last_name', 'beneficiary_first_name', 'beneficiary_birthdate'], 'inst_ref_beneficiary_match_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_referrals');
    }
};
