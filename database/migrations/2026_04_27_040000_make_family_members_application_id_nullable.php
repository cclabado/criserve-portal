<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('family_members', function (Blueprint $table) {
                $table->foreignId('application_id')->nullable()->change();
            });

            return;
        }

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->nullable()->change();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('family_members', function (Blueprint $table) {
                $table->foreignId('application_id')->nullable(false)->change();
            });

            return;
        }

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->nullable(false)->change();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
        });
    }
};
