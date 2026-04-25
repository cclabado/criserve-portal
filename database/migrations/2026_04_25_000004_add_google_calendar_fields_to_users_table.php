<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_email')->nullable()->after('civil_status');
            $table->text('google_access_token')->nullable()->after('google_email');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->dateTime('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->dateTime('google_calendar_connected_at')->nullable()->after('google_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_email',
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_calendar_connected_at',
            ]);
        });
    }
};
