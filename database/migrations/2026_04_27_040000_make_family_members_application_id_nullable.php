<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE family_members DROP FOREIGN KEY family_members_application_id_foreign');
        DB::statement('ALTER TABLE family_members MODIFY application_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE family_members ADD CONSTRAINT family_members_application_id_foreign FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE family_members DROP FOREIGN KEY family_members_application_id_foreign');
        DB::statement('ALTER TABLE family_members MODIFY application_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE family_members ADD CONSTRAINT family_members_application_id_foreign FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE');
    }
};
