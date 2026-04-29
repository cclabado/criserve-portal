<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->integer('seasonal_worker_members')->nullable()->after('working_members');
            $table->boolean('has_insurance_coverage')->nullable()->after('seasonal_worker_members');
            $table->boolean('has_savings')->nullable()->after('has_insurance_coverage');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'seasonal_worker_members',
                'has_insurance_coverage',
                'has_savings',
            ]);
        });
    }
};
