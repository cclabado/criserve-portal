<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gl_finance_batches', function (Blueprint $table) {
            $table->string('program_approval_status')->nullable()->after('withholding_tax_amount')->index();
            $table->text('program_approval_remarks')->nullable()->after('program_approval_status');
            $table->foreignId('program_approved_by')->nullable()->after('program_approval_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('program_approved_at')->nullable()->after('program_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('gl_finance_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_approved_by');
            $table->dropColumn([
                'program_approval_status',
                'program_approval_remarks',
                'program_approved_at',
            ]);
        });
    }
};
