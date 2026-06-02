<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['last_name', 'first_name', 'birthdate'], 'clients_name_birthdate_idx');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index(['last_name', 'first_name', 'birthdate'], 'beneficiaries_name_birthdate_idx');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->index(['linked_user_id', 'application_id'], 'family_members_linked_user_application_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_name_birthdate_idx');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('beneficiaries_name_birthdate_idx');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropIndex('family_members_linked_user_application_idx');
        });
    }
};
