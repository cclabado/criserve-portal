<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_fund_cluster')->nullable()->after('gl_finance_fund_source');
            $table->string('gl_responsibility_center')->nullable()->after('gl_fund_cluster');
            $table->string('gl_mfo_pap', 30)->nullable()->after('gl_responsibility_center');
            $table->string('gl_mode_of_payment', 50)->nullable()->after('gl_mfo_pap');
            $table->string('gl_payee_tin')->nullable()->after('gl_mode_of_payment');
            $table->string('gl_ors_number', 50)->nullable()->after('gl_payee_tin');
            $table->date('gl_ors_date')->nullable()->after('gl_ors_number');
            $table->string('gl_dv_number', 50)->nullable()->after('gl_ors_date');
            $table->date('gl_dv_date')->nullable()->after('gl_dv_number');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'gl_fund_cluster',
                'gl_responsibility_center',
                'gl_mfo_pap',
                'gl_mode_of_payment',
                'gl_payee_tin',
                'gl_ors_number',
                'gl_ors_date',
                'gl_dv_number',
                'gl_dv_date',
            ]);
        });
    }
};
