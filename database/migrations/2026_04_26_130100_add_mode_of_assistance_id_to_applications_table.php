<?php

use App\Models\ModeOfAssistance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('mode_of_assistance_id')
                ->nullable()
                ->after('assistance_detail_id')
                ->constrained('mode_of_assistances')
                ->nullOnDelete();
        });

        $modes = collect([
            'Cash',
            'Guarantee Letter',
            'Referral',
        ])->mapWithKeys(function (string $name) {
            $mode = ModeOfAssistance::firstOrCreate(['name' => $name]);
            return [strtolower($name) => $mode->id];
        });

        DB::table('applications')->orderBy('id')->get(['id', 'mode_of_assistance'])->each(function ($application) use ($modes) {
            $raw = strtolower(trim((string) $application->mode_of_assistance));

            $normalized = match ($raw) {
                'cash' => 'cash',
                'gl', 'guarantee letter' => 'guarantee letter',
                'referral' => 'referral',
                default => null,
            };

            if (! $normalized || ! $modes->has($normalized)) {
                return;
            }

            DB::table('applications')
                ->where('id', $application->id)
                ->update(['mode_of_assistance_id' => $modes[$normalized]]);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mode_of_assistance_id');
        });
    }
};
