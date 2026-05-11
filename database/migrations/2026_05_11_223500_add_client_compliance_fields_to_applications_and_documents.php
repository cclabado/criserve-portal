<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('client_compliance_status')->nullable()->after('status');
            $table->text('client_compliance_notes')->nullable()->after('client_compliance_status');
            $table->timestamp('client_compliance_requested_at')->nullable()->after('client_compliance_notes');
            $table->timestamp('client_compliance_responded_at')->nullable()->after('client_compliance_requested_at');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('requires_client_resubmission')->default(false)->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('requires_client_resubmission');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'client_compliance_status',
                'client_compliance_notes',
                'client_compliance_requested_at',
                'client_compliance_responded_at',
            ]);
        });
    }
};
