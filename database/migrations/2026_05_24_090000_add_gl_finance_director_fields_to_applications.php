<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_finance_director_status')->nullable()->after('gl_cash_certified_at');
            $table->text('gl_finance_director_remarks')->nullable()->after('gl_finance_director_status');
            $table->foreignId('gl_finance_director_approved_by')->nullable()->after('gl_finance_director_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('gl_finance_director_approved_at')->nullable()->after('gl_finance_director_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_finance_director_approved_by');
            $table->dropColumn([
                'gl_finance_director_status',
                'gl_finance_director_remarks',
                'gl_finance_director_approved_at',
            ]);
        });
    }
};
