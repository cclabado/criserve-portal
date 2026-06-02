<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->index(['mode_of_assistance_id', 'status', 'updated_at'], 'app_mode_status_updated_idx');
            $table->index(['service_provider_id', 'status', 'updated_at'], 'app_provider_status_updated_idx');
            $table->index(['service_provider_id', 'gl_payment_status'], 'app_provider_gl_payment_idx');
            $table->index(['gl_payment_status', 'updated_at'], 'app_gl_payment_updated_idx');
            $table->index(['gl_payment_status', 'gl_program_approval_status'], 'app_gl_payment_program_approval_idx');
            $table->index(['gl_payment_status', 'gl_budget_approval_status'], 'app_gl_payment_budget_approval_idx');
            $table->index(['gl_payment_status', 'gl_accounting_review_status'], 'app_gl_payment_accounting_review_idx');
            $table->index(['gl_payment_status', 'gl_accounting_approval_status'], 'app_gl_payment_accounting_approval_idx');
            $table->index(['gl_payment_status', 'gl_program_amount_approval_status'], 'app_gl_payment_program_amount_idx');
            $table->index(['gl_payment_status', 'gl_cash_review_status'], 'app_gl_payment_cash_review_idx');
            $table->index(['gl_payment_status', 'gl_cash_approval_status'], 'app_gl_payment_cash_approval_idx');
            $table->index(['gl_payment_status', 'gl_cash_certification_status'], 'app_gl_payment_cash_certification_idx');
            $table->index(['gl_payment_status', 'gl_finance_director_status'], 'app_gl_payment_finance_director_idx');
            $table->index(['gl_soa_status', 'updated_at'], 'app_gl_soa_status_updated_idx');
            $table->index(['gl_soa_reviewed_by', 'updated_at'], 'app_gl_soa_reviewer_updated_idx');
            $table->index(['gl_program_approved_by', 'updated_at'], 'app_gl_program_approver_updated_idx');
            $table->index(['gl_budget_reviewed_by', 'updated_at'], 'app_gl_budget_reviewer_updated_idx');
            $table->index(['gl_budget_approved_by', 'updated_at'], 'app_gl_budget_approver_updated_idx');
            $table->index(['gl_accounting_reviewed_by', 'updated_at'], 'app_gl_accounting_reviewer_updated_idx');
            $table->index(['gl_accounting_approved_by', 'updated_at'], 'app_gl_accounting_approver_updated_idx');
            $table->index(['gl_program_amount_approved_by', 'updated_at'], 'app_gl_program_amount_approver_updated_idx');
            $table->index(['gl_cash_reviewed_by', 'updated_at'], 'app_gl_cash_reviewer_updated_idx');
            $table->index(['gl_cash_approved_by', 'updated_at'], 'app_gl_cash_approver_updated_idx');
            $table->index(['gl_cash_certified_by', 'updated_at'], 'app_gl_cash_certifier_updated_idx');
            $table->index(['gl_finance_director_approved_by', 'updated_at'], 'app_gl_finance_director_approver_updated_idx');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index(['application_id', 'document_type', 'created_at'], 'docs_application_type_created_idx');
            $table->index(['document_type', 'created_at'], 'docs_type_created_idx');
            $table->index(['service_provider_bank_account_id', 'created_at'], 'docs_bank_account_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('app_mode_status_updated_idx');
            $table->dropIndex('app_provider_status_updated_idx');
            $table->dropIndex('app_provider_gl_payment_idx');
            $table->dropIndex('app_gl_payment_updated_idx');
            $table->dropIndex('app_gl_payment_program_approval_idx');
            $table->dropIndex('app_gl_payment_budget_approval_idx');
            $table->dropIndex('app_gl_payment_accounting_review_idx');
            $table->dropIndex('app_gl_payment_accounting_approval_idx');
            $table->dropIndex('app_gl_payment_program_amount_idx');
            $table->dropIndex('app_gl_payment_cash_review_idx');
            $table->dropIndex('app_gl_payment_cash_approval_idx');
            $table->dropIndex('app_gl_payment_cash_certification_idx');
            $table->dropIndex('app_gl_payment_finance_director_idx');
            $table->dropIndex('app_gl_soa_status_updated_idx');
            $table->dropIndex('app_gl_soa_reviewer_updated_idx');
            $table->dropIndex('app_gl_program_approver_updated_idx');
            $table->dropIndex('app_gl_budget_reviewer_updated_idx');
            $table->dropIndex('app_gl_budget_approver_updated_idx');
            $table->dropIndex('app_gl_accounting_reviewer_updated_idx');
            $table->dropIndex('app_gl_accounting_approver_updated_idx');
            $table->dropIndex('app_gl_program_amount_approver_updated_idx');
            $table->dropIndex('app_gl_cash_reviewer_updated_idx');
            $table->dropIndex('app_gl_cash_approver_updated_idx');
            $table->dropIndex('app_gl_cash_certifier_updated_idx');
            $table->dropIndex('app_gl_finance_director_approver_updated_idx');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('docs_application_type_created_idx');
            $table->dropIndex('docs_type_created_idx');
            $table->dropIndex('docs_bank_account_created_idx');
        });
    }
};
