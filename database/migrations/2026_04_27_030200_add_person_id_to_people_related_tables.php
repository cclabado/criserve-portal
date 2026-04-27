<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('people')->nullOnDelete();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('user_id')->constrained('people')->nullOnDelete();
        });

        Schema::table('beneficiary_profiles', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('linked_user_id')->constrained('people')->nullOnDelete();
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('beneficiary_profile_id')->constrained('people')->nullOnDelete();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('linked_user_id')->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::table('beneficiary_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
