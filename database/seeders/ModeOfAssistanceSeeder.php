<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ModeOfAssistance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModeOfAssistanceSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [];

        foreach (['Cash', 'Guarantee Letter'] as $name) {
            $mode = ModeOfAssistance::firstOrCreate(['name' => $name]);
            $modes[strtolower($name)] = $mode;
        }

        foreach (Application::query()->get(['id', 'mode_of_assistance', 'mode_of_assistance_id']) as $application) {
            $raw = strtolower(trim((string) $application->mode_of_assistance));

            $normalized = match ($raw) {
                'cash' => 'cash',
                'gl', 'guarantee letter' => 'guarantee letter',
                default => null,
            };

            if (! $normalized || ! isset($modes[$normalized])) {
                continue;
            }

            DB::table('applications')
                ->where('id', $application->id)
                ->update([
                    'mode_of_assistance_id' => $modes[$normalized]->id,
                    'mode_of_assistance' => $modes[$normalized]->name,
                ]);
        }
    }
}
