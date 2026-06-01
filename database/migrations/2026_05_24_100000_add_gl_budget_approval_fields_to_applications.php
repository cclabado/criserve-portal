<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_budget_approval_status')->nullable()->after('gl_budget_reviewed_at');
            $table->text('gl_budget_approval_remarks')->nullable()->after('gl_budget_approval_status');
            $table->foreignId('gl_budget_approved_by')->nullable()->after('gl_budget_approval_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('gl_budget_approved_at')->nullable()->after('gl_budget_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_budget_approved_by');
            $table->dropColumn([
                'gl_budget_approval_status',
                'gl_budget_approval_remarks',
                'gl_budget_approved_at',
            ]);
        });
    }
};
