<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_lddap_ada_number')->nullable()->after('gl_dv_date');
            $table->date('gl_lddap_ada_date')->nullable()->after('gl_lddap_ada_number');
            $table->string('gl_nca_number')->nullable()->after('gl_lddap_ada_date');
            $table->date('gl_nca_date')->nullable()->after('gl_nca_number');
            $table->string('gl_servicing_bank_branch')->nullable()->after('gl_nca_date');
            $table->string('gl_mds_sub_account_number')->nullable()->after('gl_servicing_bank_branch');
            $table->decimal('gl_withholding_tax_amount', 12, 2)->nullable()->after('gl_mds_sub_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'gl_lddap_ada_number',
                'gl_lddap_ada_date',
                'gl_nca_number',
                'gl_nca_date',
                'gl_servicing_bank_branch',
                'gl_mds_sub_account_number',
                'gl_withholding_tax_amount',
            ]);
        });
    }
};
