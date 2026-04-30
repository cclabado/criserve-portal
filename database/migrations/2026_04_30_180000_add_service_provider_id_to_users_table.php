<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('service_provider_id')
                ->nullable()
                ->after('person_id')
                ->constrained('service_providers')
                ->nullOnDelete();
        });

        DB::table('service_providers')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id'])
            ->each(function ($provider) {
                DB::table('users')
                    ->where('id', $provider->user_id)
                    ->update(['service_provider_id' => $provider->id]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_provider_id');
        });
    }
};
