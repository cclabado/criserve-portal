<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssistanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $library = [
                'Financial Assistance' => [
                    'Medical Assistance',
                    'Funeral Assistance',
                    'Transportation Assistance',
                    'Cash Relief Assistance',
                ],
                'Material Assistance' => [
                    'Family Food Packs/Other Food Items',
                    'Hygiene and Sleeping Kits',
                    'Assistive Device and Technology',
                ],
                'Mental Health and Psychosocial Support (MHPSS)' => [
                    'Wireless Mental Health and Psychosocial Support (WiSUPPORT)',
                    'Face-to-face Counseling with Psychological First Aid/Psychosocial Support',
                ],
                'Referral to Other Services' => [
                    'Legal Services',
                    'Psychosocial Interventions',
                    'Temporary Shelter / Residential Facilities',
                    'LSWDO Case Management',
                ],
            ];

            $typeModels = [];
            $subtypeModels = [];

            foreach ($library as $typeName => $subtypes) {
                $typeModels[$typeName] = AssistanceType::firstOrCreate([
                    'name' => $typeName,
                ]);

                foreach ($subtypes as $subtypeName) {
                    $subtypeModels[$typeName][$subtypeName] = AssistanceSubtype::firstOrCreate([
                        'assistance_type_id' => $typeModels[$typeName]->id,
                        'name' => $subtypeName,
                    ]);
                }
            }

            $detailLibrary = [
                'Medical Assistance' => [
                    'Payment for Hospital Bill',
                    'Medicines / Assistive Devices',
                    'Medical Procedures',
                    'Chemotherapy and Other Special Treatment',
                ],
                'Funeral Assistance' => [
                    'Funeral Expenses',
                    'Transfer of Cadaver',
                    'Casualties during Disaster / Calamity',
                ],
            ];

            foreach ($detailLibrary as $subtypeName => $details) {
                $subtype = AssistanceSubtype::where('name', $subtypeName)->first();

                if (! $subtype) {
                    continue;
                }

                foreach ($details as $detailName) {
                    AssistanceDetail::firstOrCreate([
                        'assistance_subtype_id' => $subtype->id,
                        'name' => $detailName,
                    ]);
                }
            }

            $legacyMappings = [
                'Medical Assistance' => [
                    'type' => 'Financial Assistance',
                    'subtype' => 'Medical Assistance',
                ],
                'Funeral Assistance' => [
                    'type' => 'Financial Assistance',
                    'subtype' => 'Funeral Assistance',
                ],
                'Transportation Assistance' => [
                    'type' => 'Financial Assistance',
                    'subtype' => 'Transportation Assistance',
                ],
            ];

            foreach ($legacyMappings as $legacyTypeName => $target) {
                $legacyType = AssistanceType::where('name', $legacyTypeName)->first();

                if (! $legacyType || $legacyType->id === $typeModels[$target['type']]->id) {
                    continue;
                }

                Application::where('assistance_type_id', $legacyType->id)->update([
                    'assistance_type_id' => $typeModels[$target['type']]->id,
                    'assistance_subtype_id' => $subtypeModels[$target['type']][$target['subtype']]->id,
                ]);

                AssistanceSubtype::where('assistance_type_id', $legacyType->id)->delete();
                $legacyType->delete();
            }

            $legacySubtypeMappings = [
                'Hospital Bill' => 'Medical Assistance',
                'Medicines' => 'Medical Assistance',
                'Laboratory' => 'Medical Assistance',
                'Burial' => 'Funeral Assistance',
                'Funeral Services' => 'Funeral Assistance',
                'Fare' => 'Transportation Assistance',
            ];

            foreach ($legacySubtypeMappings as $legacySubtypeName => $targetSubtypeName) {
                $legacySubtype = AssistanceSubtype::where('name', $legacySubtypeName)->first();

                if (! $legacySubtype) {
                    continue;
                }

                $targetSubtype = collect($subtypeModels)->flatten()
                    ->firstWhere('name', $targetSubtypeName);

                if (! $targetSubtype || $legacySubtype->id === $targetSubtype->id) {
                    continue;
                }

                Application::where('assistance_subtype_id', $legacySubtype->id)->update([
                    'assistance_type_id' => $targetSubtype->assistance_type_id,
                    'assistance_subtype_id' => $targetSubtype->id,
                ]);

                $legacySubtype->delete();
            }
        });
    }
}
