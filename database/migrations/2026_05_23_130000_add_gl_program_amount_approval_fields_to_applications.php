<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_program_amount_approval_status')->nullable()->after('gl_program_approved_at');
            $table->text('gl_program_amount_approval_remarks')->nullable()->after('gl_program_amount_approval_status');
            $table->foreignId('gl_program_amount_approved_by')->nullable()->after('gl_program_amount_approval_remarks')->constrained('users')->nullOnDelete();
            $table->dateTime('gl_program_amount_approved_at')->nullable()->after('gl_program_amount_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_program_amount_approved_by');
            $table->dropColumn([
                'gl_program_amount_approval_status',
                'gl_program_amount_approval_remarks',
                'gl_program_amount_approved_at',
            ]);
        });
    }
};
