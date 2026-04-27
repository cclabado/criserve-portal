<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AssistanceFrequencyRule;
use Carbon\Carbon;

class FrequencyEligibilityService
{
    public function evaluate(array $payload, ?Application $currentApplication = null): array
    {
        $rule = $this->resolveRule(
            $payload['assistance_subtype_id'] ?? null,
            $payload['assistance_detail_id'] ?? null
        );

        if (! $rule) {
            return [
                'rule' => null,
                'status' => 'not_applicable',
                'message' => 'No frequency rule is configured for this assistance selection yet.',
                'basis_application_id' => null,
            ];
        }

        $referenceDate = now()->startOfDay();
        $caseKey = $this->normalizeCaseKey($payload['frequency_case_key'] ?? null);
        $justification = trim((string) ($payload['frequency_override_reason'] ?? ''));

        if ($rule->requires_case_key && $caseKey === null) {
            return [
                'rule' => $rule,
                'status' => 'review_required',
                'message' => 'Frequency review needs an incident or admission reference before this assistance can be verified.',
                'basis_application_id' => null,
            ];
        }

        $priorApplication = $this->resolvePriorApplication($payload, $rule, $caseKey, $currentApplication);

        if (! $priorApplication) {
            return [
                'rule' => $rule,
                'status' => 'eligible',
                'message' => $rule->notes ?: 'No conflicting prior availment found for this rule.',
                'basis_application_id' => null,
            ];
        }

        $priorDate = $priorApplication->updated_at
            ? $priorApplication->updated_at->copy()->startOfDay()
            : $priorApplication->created_at->copy()->startOfDay();

        return match ($rule->rule_type) {
            'once_per_year' => $this->evaluateWindowRule($rule, $priorApplication, $priorDate, $referenceDate, $justification, 'year'),
            'every_n_months' => $this->evaluateMonthRule($rule, $priorApplication, $priorDate, $referenceDate),
            'every_n_months_review' => $this->evaluateMonthReviewRule($rule, $priorApplication, $priorDate, $referenceDate),
            'per_incident', 'per_admission' => $this->evaluateIncidentRule($rule, $priorApplication),
            default => [
                'rule' => $rule,
                'status' => 'review_required',
                'message' => 'A frequency rule exists, but it needs manual review before processing.',
                'basis_application_id' => $priorApplication->id,
            ],
        };
    }

    protected function resolveRule(?int $subtypeId, ?int $detailId): ?AssistanceFrequencyRule
    {
        if ($detailId) {
            $detailRule = AssistanceFrequencyRule::where('assistance_detail_id', $detailId)->first();

            if ($detailRule) {
                return $detailRule;
            }
        }

        if (! $subtypeId) {
            return null;
        }

        return AssistanceFrequencyRule::where('assistance_subtype_id', $subtypeId)
            ->whereNull('assistance_detail_id')
            ->first();
    }

    protected function resolvePriorApplication(array $payload, AssistanceFrequencyRule $rule, ?string $caseKey, ?Application $currentApplication): ?Application
    {
        $query = Application::query()
            ->where('status', 'released')
            ->where('assistance_subtype_id', $payload['assistance_subtype_id']);

        if ($rule->assistance_detail_id) {
            $query->where('assistance_detail_id', $rule->assistance_detail_id);
        }

        if (($payload['frequency_subject'] ?? 'client') === 'beneficiary') {
            if (empty($payload['beneficiary_profile_id'])) {
                return null;
            }

            $query->where('beneficiary_profile_id', $payload['beneficiary_profile_id']);
        } else {
            $query->where('client_id', $payload['client_id'])
                ->whereNull('beneficiary_profile_id');
        }

        if ($currentApplication) {
            $query->where('id', '!=', $currentApplication->id);
        }

        if (in_array($rule->rule_type, ['per_incident', 'per_admission'], true) && $caseKey) {
            $query->whereRaw('LOWER(TRIM(frequency_case_key)) = ?', [$caseKey]);
        }

        return $query->latest('updated_at')->latest('id')->first();
    }

    protected function evaluateWindowRule(
        AssistanceFrequencyRule $rule,
        Application $priorApplication,
        Carbon $priorDate,
        Carbon $referenceDate,
        string $exceptionReason,
        string $windowLabel
    ): array {
        if ($priorDate->copy()->addYear()->greaterThan($referenceDate)) {
            if ($rule->allows_exception_request && $exceptionReason !== '') {
                return [
                    'rule' => $rule,
                    'status' => 'review_required',
                    'message' => 'A prior availment exists within the last '.$windowLabel.'. Exception review is required before proceeding.',
                    'basis_application_id' => $priorApplication->id,
                ];
            }

            return [
                'rule' => $rule,
                'status' => 'blocked',
                'message' => 'A prior availment already exists within the last '.$windowLabel.'.',
                'basis_application_id' => $priorApplication->id,
            ];
        }

        return [
            'rule' => $rule,
            'status' => 'eligible',
            'message' => $rule->notes ?: 'Frequency rule satisfied.',
            'basis_application_id' => $priorApplication->id,
        ];
    }

    protected function evaluateMonthRule(AssistanceFrequencyRule $rule, Application $priorApplication, Carbon $priorDate, Carbon $referenceDate): array
    {
        if ($priorDate->addMonthsNoOverflow((int) $rule->interval_months)->greaterThan($referenceDate)) {
            return [
                'rule' => $rule,
                'status' => 'blocked',
                'message' => 'This assistance is limited to once every '.$rule->interval_months.' months.',
                'basis_application_id' => $priorApplication->id,
            ];
        }

        return [
            'rule' => $rule,
            'status' => 'eligible',
            'message' => $rule->notes ?: 'Frequency rule satisfied.',
            'basis_application_id' => $priorApplication->id,
        ];
    }

    protected function evaluateMonthReviewRule(AssistanceFrequencyRule $rule, Application $priorApplication, Carbon $priorDate, Carbon $referenceDate): array
    {
        if ($priorDate->addMonthsNoOverflow((int) $rule->interval_months)->greaterThan($referenceDate)) {
            return [
                'rule' => $rule,
                'status' => 'review_required',
                'message' => 'A prior availment exists within '.$rule->interval_months.' months. Social worker review is required because this detail may cover medicine or device cases with different rules.',
                'basis_application_id' => $priorApplication->id,
            ];
        }

        return [
            'rule' => $rule,
            'status' => 'eligible',
            'message' => $rule->notes ?: 'Frequency rule satisfied.',
            'basis_application_id' => $priorApplication->id,
        ];
    }

    protected function evaluateIncidentRule(AssistanceFrequencyRule $rule, Application $priorApplication): array
    {
        return [
            'rule' => $rule,
            'status' => 'blocked',
            'message' => 'This assistance was already used for the same incident or admission reference.',
            'basis_application_id' => $priorApplication->id,
        ];
    }

    protected function normalizeCaseKey(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}
