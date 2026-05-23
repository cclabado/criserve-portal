<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_cash')
            ->where('gl_cash_approval_status', 'approved')
            ->where(function ($query) {
                $query->where('gl_cash_certification_status', 'pending_approval')
                    ->orWhereNull('gl_cash_certification_status');
            })
            ->update([
                'gl_payment_status' => 'for_processing_accounting_certification',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_accounting_certification')
            ->where('gl_cash_approval_status', 'approved')
            ->update([
                'gl_payment_status' => 'for_processing_cash',
                'updated_at' => now(),
            ]);
    }
};
