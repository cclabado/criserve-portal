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
            $table->string('gl_cash_review_status')->nullable()->after('gl_accounting_approved_at');
            $table->text('gl_cash_remarks')->nullable()->after('gl_cash_review_status');
            $table->foreignId('gl_cash_reviewed_by')->nullable()->after('gl_cash_remarks')->constrained('users')->nullOnDelete();
            $table->dateTime('gl_cash_reviewed_at')->nullable()->after('gl_cash_reviewed_by');
            $table->string('gl_cash_approval_status')->nullable()->after('gl_cash_reviewed_at');
            $table->text('gl_cash_approval_remarks')->nullable()->after('gl_cash_approval_status');
            $table->foreignId('gl_cash_approved_by')->nullable()->after('gl_cash_approval_remarks')->constrained('users')->nullOnDelete();
            $table->dateTime('gl_cash_approved_at')->nullable()->after('gl_cash_approved_by');
        });

        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where('gl_program_amount_approval_status', 'approved')
            ->update([
                'gl_payment_status' => 'for_processing_cash',
                'gl_cash_review_status' => 'pending_review',
            ]);
    }

    public function down(): void
    {
        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_cash')
            ->where('gl_program_amount_approval_status', 'approved')
            ->where('gl_cash_review_status', 'pending_review')
            ->update([
                'gl_payment_status' => 'for_processing_program_amount_approval',
                'gl_cash_review_status' => null,
            ]);

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_cash_reviewed_by');
            $table->dropConstrainedForeignId('gl_cash_approved_by');
            $table->dropColumn([
                'gl_cash_review_status',
                'gl_cash_remarks',
                'gl_cash_reviewed_at',
                'gl_cash_approval_status',
                'gl_cash_approval_remarks',
                'gl_cash_approved_at',
            ]);
        });
    }
};
