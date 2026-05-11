<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('google_calendar_connected_at');
            $table->string('signature_disk')->nullable()->after('signature_path');
            $table->string('signature_mime_type')->nullable()->after('signature_disk');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('client_signature_path')->nullable()->after('gl_soa_reviewed_at');
            $table->string('client_signature_disk')->nullable()->after('client_signature_path');
            $table->string('client_signature_mime_type')->nullable()->after('client_signature_disk');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'client_signature_path',
                'client_signature_disk',
                'client_signature_mime_type',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'signature_path',
                'signature_disk',
                'signature_mime_type',
            ]);
        });
    }
};
