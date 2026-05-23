<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_accounting_review_status')->nullable()->after('gl_program_approved_at');
            $table->text('gl_accounting_remarks')->nullable()->after('gl_accounting_review_status');
            $table->foreignId('gl_accounting_reviewed_by')->nullable()->after('gl_accounting_remarks')->constrained('users')->nullOnDelete();
            $table->dateTime('gl_accounting_reviewed_at')->nullable()->after('gl_accounting_reviewed_by');
            $table->string('gl_accounting_approval_status')->nullable()->after('gl_accounting_reviewed_at');
            $table->text('gl_accounting_approval_remarks')->nullable()->after('gl_accounting_approval_status');
            $table->foreignId('gl_accounting_approved_by')->nullable()->after('gl_accounting_approval_remarks')->constrained('users')->nullOnDelete();
            $table->dateTime('gl_accounting_approved_at')->nullable()->after('gl_accounting_approved_by');
        });

        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_budget')
            ->where('gl_program_approval_status', 'approved')
            ->update([
                'gl_payment_status' => 'for_processing_accounting',
                'gl_accounting_review_status' => 'pending_review',
            ]);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_accounting_reviewed_by');
            $table->dropConstrainedForeignId('gl_accounting_approved_by');
            $table->dropColumn([
                'gl_accounting_review_status',
                'gl_accounting_remarks',
                'gl_accounting_reviewed_at',
                'gl_accounting_approval_status',
                'gl_accounting_approval_remarks',
                'gl_accounting_approved_at',
            ]);
        });
    }
};
