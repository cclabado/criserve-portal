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
            'Partner',
            'Child',
            'Son',
            'Daughter',
            'Sibling',
            'Brother',
            'Sister',
            'Grandmother',
            'Grandfather',
            'Grandparent',
            'Grandchild',
            'Grandson',
            'Granddaughter',
            'Aunt',
            'Uncle',
            'Niece',
            'Nephew',
            'Cousin',
            'Mother-in-law',
            'Father-in-law',
            'Son-in-law',
            'Daughter-in-law',
            'Brother-in-law',
            'Sister-in-law',
            'Stepfather',
            'Stepmother',
            'Stepson',
            'Stepdaughter',
            'Stepchild',
            'Stepsibling',
            'Half-brother',
            'Half-sister',
            'Relative',
            'Guardian',
            'Legal Guardian',
            'Ward',
            'Caregiver',
            'Foster Parent',
            'Foster Child',
            'Other'
        ];

        foreach ($data as $item) {
            Relationship::firstOrCreate([
                'name' => $item,
            ]);
        }
    }
}
