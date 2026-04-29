<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistance_types', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::table('assistance_subtypes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::table('assistance_details', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::table('mode_of_assistances', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::table('relationships', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('assistance_types', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('assistance_subtypes', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('assistance_details', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('mode_of_assistances', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('relationships', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
