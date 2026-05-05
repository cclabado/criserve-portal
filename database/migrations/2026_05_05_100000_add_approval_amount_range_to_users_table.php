<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('approval_min_amount', 12, 2)
                ->nullable()
                ->after('service_provider_id');
            $table->decimal('approval_max_amount', 12, 2)
                ->nullable()
                ->after('approval_min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approval_min_amount',
                'approval_max_amount',
            ]);
        });
    }
};
