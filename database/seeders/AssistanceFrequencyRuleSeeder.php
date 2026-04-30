<?php

namespace Database\Seeders;

use App\Models\AssistanceDetail;
use App\Models\AssistanceFrequencyRule;
use App\Models\AssistanceSubtype;
use Illuminate\Database\Seeder;

class AssistanceFrequencyRuleSeeder extends Seeder
{
    public function run(): void
    {
        $subtypeRules = [
            'Transportation Assistance' => [
                'rule_type' => 'once_per_year',
                'interval_months' => 12,
                'allows_exception_request' => true,
                'notes' => 'General rule: once a year for transportation assistance. Exception review may apply for consecutive family deaths or medical travel needs.',
            ],
            'Cash Relief Assistance' => [
                'rule_type' => 'per_incident',
                'requires_case_key' => true,
                'notes' => 'Once for every applicable incident.',
            ],
        ];

        foreach ($subtypeRules as $subtypeName => $payload) {
            $subtype = AssistanceSubtype::where('name', $subtypeName)->first();

            if (! $subtype) {
                continue;
            }

            AssistanceFrequencyRule::updateOrCreate(
                [
                    'assistance_subtype_id' => $subtype->id,
                    'assistance_detail_id' => null,
                ],
                $payload
            );
        }

        $detailRules = [
            'Payment for Hospital Bill' => [
                'rule_type' => 'per_admission',
                'requires_case_key' => true,
                'notes' => 'Allowed once for every hospital admission.',
            ],
            'Medicines / Assistive Devices' => [
                'rule_type' => 'every_n_months_review',
                'interval_months' => 3,
                'notes' => 'Review against medicine or assistive device frequency. Medical worker must confirm whether 3-month or incident-based rule applies.',
            ],
            'Medical Procedures' => [
                'rule_type' => 'every_n_months',
                'interval_months' => 6,
                'notes' => 'Allowed once every six months.',
            ],
            'Chemotherapy and Other Special Treatment' => [
                'rule_type' => 'every_n_months',
                'interval_months' => 3,
                'notes' => 'Allowed once every three months.',
            ],
            'Funeral Bill Payment' => [
                'rule_type' => 'per_incident',
                'requires_case_key' => true,
                'notes' => 'General rule: per beneficiary or incident of death.',
            ],
            'Transfer of Remains (Cadaver Transfer Assistance)' => [
                'rule_type' => 'per_incident',
                'requires_case_key' => true,
                'notes' => 'Allowed per incident of death.',
            ],
        ];

        foreach ($detailRules as $detailName => $payload) {
            $detail = AssistanceDetail::where('name', $detailName)->first();

            if (! $detail) {
                continue;
            }

            AssistanceFrequencyRule::updateOrCreate(
                [
                    'assistance_subtype_id' => $detail->assistance_subtype_id,
                    'assistance_detail_id' => $detail->id,
                ],
                $payload
            );
        }

        $transportationSubtype = AssistanceSubtype::where('name', 'Transportation Assistance')->first();

        if ($transportationSubtype) {
            $transportationDetailIds = AssistanceDetail::where('assistance_subtype_id', $transportationSubtype->id)
                ->pluck('id');

            if ($transportationDetailIds->isNotEmpty()) {
                AssistanceFrequencyRule::whereIn('assistance_detail_id', $transportationDetailIds)->delete();
            }
        }
    }
}
