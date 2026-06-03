<?php

namespace Database\Seeders;

use App\Models\FinanceFundSource;
use Illuminate\Database\Seeder;

class FinanceFundSourceSeeder extends Seeder
{
    /**
     * Seed the application's finance fund source library.
     */
    public function run(): void
    {
        $sources = [
            'Regular Fund',
            'Quick Response Fund',
            'Trust Fund',
            'LGU Counterpart Fund',
            'Special Assistance Fund',
            'PSIF Regular Fund',
            'AKAP Fund',
        ];

        foreach ($sources as $source) {
            FinanceFundSource::updateOrCreate(
                ['name' => $source],
                ['is_active' => true]
            );
        }
    }
}
