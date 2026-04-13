<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssistanceType;
use App\Models\AssistanceSubtype;

class AssistanceSeeder extends Seeder
{
    public function run()
    {
        // MEDICAL
        $medical = AssistanceType::create([
            'name' => 'Medical Assistance'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $medical->id,
            'name' => 'Hospital Bill'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $medical->id,
            'name' => 'Medicines'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $medical->id,
            'name' => 'Laboratory'
        ]);

        // FUNERAL
        $funeral = AssistanceType::create([
            'name' => 'Funeral Assistance'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $funeral->id,
            'name' => 'Burial'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $funeral->id,
            'name' => 'Funeral Services'
        ]);

        // TRANSPORTATION
        $transport = AssistanceType::create([
            'name' => 'Transportation Assistance'
        ]);

        AssistanceSubtype::create([
            'assistance_type_id' => $transport->id,
            'name' => 'Fare'
        ]);
    }
}