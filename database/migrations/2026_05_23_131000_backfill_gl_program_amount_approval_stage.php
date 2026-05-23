<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_accounting')
            ->where('gl_accounting_approval_status', 'approved')
            ->update([
                'gl_payment_status' => 'for_processing_program_amount_approval',
                'gl_program_amount_approval_status' => DB::raw("COALESCE(gl_program_amount_approval_status, 'pending_approval')"),
            ]);
    }

    public function down(): void
    {
        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where('gl_accounting_approval_status', 'approved')
            ->where('gl_program_amount_approval_status', 'pending_approval')
            ->update([
                'gl_payment_status' => 'for_processing_accounting',
                'gl_program_amount_approval_status' => null,
            ]);
    }
};
