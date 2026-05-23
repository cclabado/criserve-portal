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
            $table->string('gl_cash_certification_status')->nullable()->after('gl_cash_approved_at');
            $table->text('gl_cash_certification_remarks')->nullable()->after('gl_cash_certification_status');
            $table->foreignId('gl_cash_certified_by')->nullable()->after('gl_cash_certification_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('gl_cash_certified_at')->nullable()->after('gl_cash_certified_by');
        });

        DB::table('applications')
            ->where('gl_payment_status', 'for_processing_cash')
            ->where('gl_cash_approval_status', 'approved')
            ->whereNull('gl_cash_certification_status')
            ->update([
                'gl_cash_certification_status' => 'pending_approval',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_cash_certified_by');
            $table->dropColumn([
                'gl_cash_certification_status',
                'gl_cash_certification_remarks',
                'gl_cash_certified_at',
            ]);
        });
    }
};
