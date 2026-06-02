<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_profiles', function (Blueprint $table) {
            $table->index(
                ['client_id', 'last_name', 'first_name', 'birthdate'],
                'bene_profiles_client_name_birthdate_idx'
            );
            $table->index(
                ['client_id', 'middle_name', 'extension_name'],
                'bene_profiles_client_middle_extension_idx'
            );
            $table->index(
                ['linked_user_id', 'birthdate'],
                'bene_profiles_linked_user_birthdate_idx'
            );
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->index(
                ['client_id', 'beneficiary_profile_id', 'application_id'],
                'family_members_client_profile_application_idx'
            );
            $table->index(
                ['beneficiary_profile_id', 'application_id'],
                'family_members_profile_application_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_profiles', function (Blueprint $table) {
            $table->dropIndex('bene_profiles_client_name_birthdate_idx');
            $table->dropIndex('bene_profiles_client_middle_extension_idx');
            $table->dropIndex('bene_profiles_linked_user_birthdate_idx');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropIndex('family_members_client_profile_application_idx');
            $table->dropIndex('family_members_profile_application_idx');
        });
    }
};
