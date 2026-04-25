<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('frequency_rule_id')
                ->nullable()
                ->after('mode_of_assistance_id')
                ->constrained('assistance_frequency_rules')
                ->nullOnDelete();
            $table->foreignId('frequency_basis_application_id')
                ->nullable()
                ->after('frequency_rule_id')
                ->constrained('applications')
                ->nullOnDelete();
            $table->string('frequency_status')->nullable()->after('frequency_basis_application_id');
            $table->text('frequency_message')->nullable()->after('frequency_status');
            $table->date('frequency_reference_date')->nullable()->after('frequency_message');
            $table->string('frequency_case_key')->nullable()->after('frequency_reference_date');
            $table->text('frequency_exception_reason')->nullable()->after('frequency_case_key');
            $table->text('frequency_override_reason')->nullable()->after('frequency_exception_reason');
            $table->timestamp('frequency_checked_at')->nullable()->after('frequency_override_reason');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('frequency_rule_id');
            $table->dropConstrainedForeignId('frequency_basis_application_id');
            $table->dropColumn([
                'frequency_status',
                'frequency_message',
                'frequency_reference_date',
                'frequency_case_key',
                'frequency_exception_reason',
                'frequency_override_reason',
                'frequency_checked_at',
            ]);
        });
    }
};
