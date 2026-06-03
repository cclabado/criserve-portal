<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_finance_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no')->unique();
            $table->foreignId('service_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_provider_bank_account_id')->constrained('service_provider_bank_accounts')->cascadeOnDelete();
            $table->string('finance_fund_source_name');
            $table->string('status')->default('draft')->index();
            $table->string('current_stage')->nullable()->index();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedInteger('application_count')->default(0);
            $table->foreignId('compliance_trigger_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batched_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('fund_cluster')->nullable();
            $table->string('responsibility_center')->nullable();
            $table->string('mfo_pap', 30)->nullable();
            $table->string('mode_of_payment', 50)->nullable();
            $table->string('payee_tin')->nullable();
            $table->string('ors_number')->nullable();
            $table->date('ors_date')->nullable();
            $table->string('dv_number')->nullable();
            $table->date('dv_date')->nullable();
            $table->string('lddap_ada_number')->nullable();
            $table->date('lddap_ada_date')->nullable();
            $table->string('nca_number')->nullable();
            $table->date('nca_date')->nullable();
            $table->string('servicing_bank_branch')->nullable();
            $table->string('mds_sub_account_number')->nullable();
            $table->decimal('withholding_tax_amount', 14, 2)->nullable();

            $table->string('budget_approval_status')->nullable()->index();
            $table->text('budget_approval_remarks')->nullable();
            $table->foreignId('budget_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('budget_approved_at')->nullable();

            $table->string('accounting_approval_status')->nullable()->index();
            $table->text('accounting_approval_remarks')->nullable();
            $table->foreignId('accounting_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounting_approved_at')->nullable();

            $table->string('program_amount_approval_status')->nullable()->index();
            $table->text('program_amount_approval_remarks')->nullable();
            $table->foreignId('program_amount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('program_amount_approved_at')->nullable();

            $table->string('cash_approval_status')->nullable()->index();
            $table->text('cash_approval_remarks')->nullable();
            $table->foreignId('cash_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cash_approved_at')->nullable();

            $table->string('accounting_certification_status')->nullable()->index();
            $table->text('accounting_certification_remarks')->nullable();
            $table->foreignId('accounting_certified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounting_certified_at')->nullable();

            $table->string('finance_director_status')->nullable()->index();
            $table->text('finance_director_remarks')->nullable();
            $table->foreignId('finance_director_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_director_approved_at')->nullable();

            $table->timestamps();

            $table->index(['service_provider_id', 'finance_fund_source_name', 'service_provider_bank_account_id'], 'gl_finance_batches_match_idx');
            $table->index(['status', 'current_stage', 'updated_at'], 'gl_finance_batches_status_stage_idx');
        });

        Schema::create('gl_finance_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_finance_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->decimal('utilized_amount', 14, 2);
            $table->string('item_status')->default('included');
            $table->boolean('flagged_for_compliance')->default(false);
            $table->text('flag_reason')->nullable();
            $table->timestamps();

            $table->unique('application_id');
            $table->unique(['gl_finance_batch_id', 'sequence_no']);
            $table->index(['gl_finance_batch_id', 'sequence_no'], 'gl_finance_batch_items_batch_seq_idx');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('gl_finance_batch_id')->nullable()->after('service_provider_id')->constrained('gl_finance_batches')->nullOnDelete();
            $table->string('gl_batch_status')->nullable()->after('gl_payment_status')->index();
            $table->timestamp('gl_ready_for_batch_at')->nullable()->after('gl_batch_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_finance_batch_id');
            $table->dropColumn(['gl_batch_status', 'gl_ready_for_batch_at']);
        });

        Schema::dropIfExists('gl_finance_batch_items');
        Schema::dropIfExists('gl_finance_batches');
    }
};
