<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('mfa_code_hash')->nullable()->after('google_calendar_connected_at');
            $table->timestamp('mfa_code_expires_at')->nullable()->after('mfa_code_hash');
            $table->timestamp('mfa_code_sent_at')->nullable()->after('mfa_code_expires_at');
            $table->text('mfa_remember_token_hash')->nullable()->after('mfa_code_sent_at');
            $table->timestamp('mfa_remember_until')->nullable()->after('mfa_remember_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_code_hash',
                'mfa_code_expires_at',
                'mfa_code_sent_at',
                'mfa_remember_token_hash',
                'mfa_remember_until',
            ]);
        });
    }
};
