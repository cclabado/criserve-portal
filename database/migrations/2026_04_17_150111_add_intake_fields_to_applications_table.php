<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {

            // Financial
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->integer('household_members')->nullable();
            $table->integer('working_members')->nullable();
            $table->decimal('monthly_expenses', 12, 2)->nullable();
            $table->decimal('savings', 12, 2)->nullable();

            // Crisis
            $table->string('crisis_type')->nullable();
            $table->string('urgency_level')->nullable();

            // Vulnerability
            $table->boolean('has_elderly')->default(false);
            $table->boolean('has_child')->default(false);
            $table->boolean('has_pwd')->default(false);
            $table->boolean('has_pregnant')->default(false);
            $table->boolean('earner_unable_to_work')->default(false);

            // Support
            $table->boolean('has_philhealth')->default(false);
            $table->boolean('has_family_support')->default(false);

            // Recommendation
            $table->decimal('recommended_amount', 12, 2)->nullable();
            $table->decimal('final_amount', 12, 2)->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_income',
                'household_members',
                'working_members',
                'monthly_expenses',
                'savings',
                'crisis_type',
                'urgency_level',
                'has_elderly',
                'has_child',
                'has_pwd',
                'has_pregnant',
                'earner_unable_to_work',
                'has_philhealth',
                'has_family_support',
                'recommended_amount',
                'final_amount',
            ]);
        });
    }
};