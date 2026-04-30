<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_points', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $timestamp = now();

        foreach (['Online', 'Onsite', 'Offsite', 'Malasakit Center'] as $name) {
            DB::table('service_points')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        DB::table('applications')
            ->where('gis_visit_type', 'AICS Onsite')
            ->update(['gis_visit_type' => 'Onsite']);

        DB::table('applications')
            ->where('gis_visit_type', 'AKAP')
            ->update(['gis_visit_type' => 'Onsite']);

        DB::table('applications')
            ->where('gis_visit_type', 'Others')
            ->update(['gis_visit_type' => 'Online']);

        DB::table('applications')
            ->where(function ($query) {
                $query->whereNull('gis_visit_type')
                    ->orWhere('gis_visit_type', '');
            })
            ->update(['gis_visit_type' => 'Online']);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_points');
    }
};
