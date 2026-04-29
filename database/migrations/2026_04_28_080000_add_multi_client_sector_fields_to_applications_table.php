<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('client_sectors')->nullable()->after('client_sector');
            $table->json('client_sub_categories')->nullable()->after('client_sub_category');
            $table->json('disability_types')->nullable()->after('disability_type');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'client_sectors',
                'client_sub_categories',
                'disability_types',
            ]);
        });
    }
};
