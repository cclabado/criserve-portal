<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Relationship;

class RelationshipSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'Self',
            'Mother',
            'Father',
            'Spouse',
            'Child',
            'Sibling',
            'Relative',
            'Guardian',
            'Other'
        ];

        foreach ($data as $item) {
            Relationship::create([
                'name' => $item
            ]);
        }
    }
}