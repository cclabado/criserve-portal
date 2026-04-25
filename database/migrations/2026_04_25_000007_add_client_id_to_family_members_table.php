<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('application_id')->constrained()->cascadeOnDelete();
        });

        DB::table('family_members')
            ->whereNull('client_id')
            ->orderBy('id')
            ->get(['id', 'application_id'])
            ->each(function ($member) {
                $clientId = Application::whereKey($member->application_id)->value('client_id');

                if ($clientId) {
                    DB::table('family_members')
                        ->where('id', $member->id)
                        ->update(['client_id' => $clientId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
