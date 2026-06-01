<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('service_provider_bank_account_id')->nullable()->after('application_id')->constrained('service_provider_bank_accounts')->nullOnDelete();
            $table->string('bank_name_snapshot')->nullable()->after('remarks');
            $table->string('account_name_snapshot')->nullable()->after('bank_name_snapshot');
            $table->string('account_number_snapshot')->nullable()->after('account_name_snapshot');
            $table->string('branch_name_snapshot')->nullable()->after('account_number_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_provider_bank_account_id');
            $table->dropColumn([
                'bank_name_snapshot',
                'account_name_snapshot',
                'account_number_snapshot',
                'branch_name_snapshot',
            ]);
        });
    }
};
