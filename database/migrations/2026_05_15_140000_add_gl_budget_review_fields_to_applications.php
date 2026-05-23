<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_finance_fund_source')->nullable()->after('gl_soa_reviewed_at');
            $table->text('gl_budget_remarks')->nullable()->after('gl_finance_fund_source');
            $table->foreignId('gl_budget_reviewed_by')->nullable()->after('gl_budget_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('gl_budget_reviewed_at')->nullable()->after('gl_budget_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_budget_reviewed_by');
            $table->dropColumn([
                'gl_finance_fund_source',
                'gl_budget_remarks',
                'gl_budget_reviewed_at',
            ]);
        });
    }
};
