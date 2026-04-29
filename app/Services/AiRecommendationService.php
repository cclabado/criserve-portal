<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiRecommendationService
{
    public function generate(Application $application, array $intakeData): array
    {
        $estimator = $this->calculateEstimator($application, $intakeData);

        if (! config('services.openai.api_key')) {
            return $this->fallback($application, $intakeData, 'OpenAI API key is not configured.');
        }

        try {
            $response = Http::baseUrl(rtrim(config('services.openai.base_url'), '/'))
                ->withToken(config('services.openai.api_key'))
                ->connectTimeout((int) config('services.openai.connect_timeout', 15))
                ->timeout((int) config('services.openai.timeout', 30))
                ->retry(2, 750, function (\Exception $exception) {
                    return $exception instanceof ConnectionException;
                })
                ->acceptJson()
                ->post('/responses', [
                    'model' => config('services.openai.model'),
                    'input' => [[
                        'role' => 'system',
                        'content' => [[
                            'type' => 'input_text',
                            'text' => $this->systemPrompt(),
                        ]],
                    ], [
                        'role' => 'user',
                        'content' => [[
                            'type' => 'input_text',
                            'text' => $this->userPrompt($application, $intakeData, $estimator),
                        ]],
                    ]],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'aid_recommendation',
                            'strict' => true,
                            'schema' => $this->schema(),
                        ],
                    ],
                ])
                ->throw();

            return $this->normalizeResponse($response->json(), $estimator);
        } catch (ConnectionException $exception) {
            return $this->fallback(
                $application,
                $intakeData,
                'AI service connection failed. A fallback recommendation was used instead.'
            );
        } catch (RequestException $exception) {
            return $this->fallback(
                $application,
                $intakeData,
                'AI request failed: '.Str::limit($exception->getMessage(), 180)
            );
        } catch (RuntimeException $exception) {
            return $this->fallback(
                $application,
                $intakeData,
                'AI response could not be parsed: '.Str::limit($exception->getMessage(), 180)
            );
        }
    }

    public function fallback(Application $application, array $intakeData, ?string $reason = null): array
    {
        $estimator = $this->calculateEstimator($application, $intakeData);
        $drivers = collect($estimator['breakdown'])
            ->sortByDesc('weighted_score')
            ->take(3)
            ->map(fn (array $row) => $row['label'].': '.$row['score'].'/'.$row['max_score'])
            ->implode('; ');

        $summary = collect([
            'Rule-based fallback used the Assistance Estimator Pointing System.',
            'Total score: '.$estimator['score'].'/96 ('.round($estimator['percentage_equivalent'] * 100, 2).'%).',
            'Strongest drivers: '.$drivers.'.',
            'Recommended amount is 75% of amount needed multiplied by the percentage equivalent.',
            $reason,
        ])->filter()->implode(' ');

        return [
            'recommended_amount' => $estimator['recommended_amount'],
            'summary' => $summary,
            'confidence' => 70,
            'source' => 'fallback_rules',
            'model' => null,
            'generated_at' => now()->toIso8601String(),
            'score' => $estimator['score'],
            'percentage_equivalent' => $estimator['percentage_equivalent'],
            'score_breakdown' => $estimator['breakdown'],
        ];
    }

    protected function normalizeResponse(array $payload, array $estimator): array
    {
        $text = Arr::get($payload, 'output_text');

        if (! $text) {
            foreach (Arr::get($payload, 'output', []) as $output) {
                foreach (Arr::get($output, 'content', []) as $content) {
                    if (($content['type'] ?? null) === 'output_text' && ! empty($content['text'])) {
                        $text = $content['text'];
                        break 2;
                    }
                }
            }
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Missing structured text output.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid JSON response.');
        }

        return [
            'recommended_amount' => $estimator['recommended_amount'],
            'summary' => trim((string) Arr::get($decoded, 'summary', '')),
            'confidence' => min(100, max(0, (int) Arr::get($decoded, 'confidence', 0))),
            'source' => 'openai',
            'model' => Arr::get($payload, 'model', config('services.openai.model')),
            'generated_at' => now()->toIso8601String(),
            'score' => $estimator['score'],
            'percentage_equivalent' => $estimator['percentage_equivalent'],
            'score_breakdown' => $estimator['breakdown'],
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are assisting a municipal social worker who is screening emergency aid requests.
Use the Assistance Estimator Pointing System exactly as provided in the case data.
The recommended amount must be based on: amount needed * 75% * percentage equivalent, with percentage equivalent equal to total score / 96. Do not exceed that computed amount.
Do not invent facts. Keep the summary to 2-4 sentences and mention the strongest scoring drivers for the recommendation.
PROMPT;
    }

    protected function userPrompt(Application $application, array $intakeData, array $estimator): string
    {
        $caseData = [
            'reference_no' => $application->reference_no,
            'assistance_type' => optional($application->assistanceType)->name,
            'assistance_subtype' => optional($application->assistanceSubtype)->name,
            'mode_of_assistance' => $application->mode_of_assistance,
            'amount_needed' => (float) ($intakeData['amount_needed'] ?? $application->amount_needed ?? 0),
            'client' => [
                'name' => trim(($application->client->first_name ?? '').' '.($application->client->last_name ?? '')),
                'address' => $application->client->full_address ?? null,
                'civil_status' => $application->client->civil_status ?? null,
            ],
            'beneficiary_present' => $application->beneficiary !== null,
            'family_members_count' => $application->familyMembers->count(),
            'intake' => $intakeData,
            'assistance_estimator' => $estimator,
        ];

        return "Case data and estimator computation:\n".json_encode($caseData, JSON_PRETTY_PRINT);
    }

    protected function calculateEstimator(Application $application, array $intakeData): array
    {
        $amountNeeded = (float) ($intakeData['amount_needed'] ?? $application->amount_needed ?? 0);
        $workingMembers = (int) ($intakeData['working_members'] ?? 0);
        $seasonalEmployees = (int) ($intakeData['seasonal_worker_members'] ?? 0);
        $income = (float) ($intakeData['monthly_income'] ?? 0);
        $expenses = (float) ($intakeData['monthly_expenses'] ?? 0);
        $insuranceCoverage = ! empty($intakeData['has_insurance_coverage'])
            || $this->hasAffirmativeText($intakeData['insurance_coverage'] ?? null);
        $hasSavings = ! empty($intakeData['has_savings'])
            || (float) ($intakeData['savings'] ?? 0) > 0;
        $hasEmergencyFund = $this->hasAffirmativeText($intakeData['emergency_fund'] ?? null);
        $supportSystems = $intakeData['support_systems'] ?? [];
        $externalResources = $intakeData['external_resources'] ?? [];
        $selfHelpEfforts = $intakeData['self_help_efforts'] ?? [];
        $recentCrises = $intakeData['recent_crisis_types'] ?? [];

        $incomeScore = 0;
        if ($workingMembers >= 1) {
            $incomeScore += 1;
        }
        if ($seasonalEmployees >= 1) {
            $incomeScore += 2;
        }
        if ($workingMembers === 0 && $seasonalEmployees === 0) {
            $incomeScore += 3;
        }
        $incomeScore += match (true) {
            $income > 50000 => 1,
            $income >= 25001 && $income <= 50000 => 2,
            $income >= 10001 && $income <= 25000 => 3,
            $income > 0 && $income <= 10000 => 4,
            default => 0,
        };
        $incomeScore += $insuranceCoverage ? 1 : 3;
        $incomeScore += $hasSavings ? 1 : 3;

        $budgetScore = ($income <= 0 || ($expenses / $income) >= 0.5 ? 4 : 2)
            + ($hasEmergencyFund ? 1 : 3);

        $severityScore = match ($intakeData['disease_duration'] ?? null) {
            'Recently diagnosed (3 months and below)', 'Recently diagnosed(3mos & below)' => 2,
            '3 months to a year' => 3,
            'Chronic or lifelong', 'chronic or lifelong' => 4,
            default => 0,
        };
        $severityScore += ! empty($intakeData['experienced_recent_crisis']) || count($recentCrises) >= 1 ? 4 : 2;

        $supportScore = collect([
            'Family' => $this->containsAny($supportSystems, ['Family']),
            'Relatives' => $this->containsAny($supportSystems, ['Employed Relatives']),
            'Friend/s' => $this->containsAny($supportSystems, ['Friend/s']),
            'Employer' => $this->containsAny($supportSystems, ['Employer']),
            'Church/Community Organization' => $this->containsAny($supportSystems, ['Church/Community Organization']),
        ])->sum(fn (bool $hasSupport) => $hasSupport ? 2 : 4);

        $externalScore = collect([
            'PhilHealth' => ! empty($intakeData['has_philhealth']) || $this->containsAny($externalResources, ['PhilHealth']),
            'Health Card' => $this->containsAny($externalResources, ['Health Card']),
            'Guarantee Letter from other agencies' => $this->containsAny($externalResources, ['Guarantee Letter from other agencies']),
            'MSS Discount' => $this->containsAny($externalResources, ['MSS Discount']),
            'Senior Citizen Discount' => $this->containsAny($externalResources, ['Senior Citizen Discount']),
            'PWD Discount' => $this->containsAny($externalResources, ['PWD Discount']),
            'Others' => $this->containsAny($externalResources, ['Others']),
        ])->sum(fn (bool $hasResource) => $hasResource ? 2 : 4);

        $selfHelpScore = collect([
            'employment' => $this->containsAny($selfHelpEfforts, ['Successfully sought employment opportunities or explored additional income sources']),
            'agency_support' => $this->containsAny($selfHelpEfforts, ['Successfully reached out to relevant organizations or agencies for financial assistance or support']),
        ])->sum(fn (bool $hasEffort) => $hasEffort ? 2 : 4);

        $vulnerableHousehold = ! empty($intakeData['has_vulnerable_household_member']);
        $inabilityToSecureEmployment = ! empty($intakeData['has_unstable_employment'])
            || $this->containsAny($recentCrises, ['Inability to secure stable employment']);
        $vulnerabilityScore = ($vulnerableHousehold ? 4 : 2)
            + (! empty($intakeData['earner_unable_to_work']) ? 4 : 2)
            + ($inabilityToSecureEmployment ? 4 : 2);

        $breakdown = [
            'income_and_financial_resources' => [
                'label' => 'Income and Financial Resources',
                'score' => $incomeScore,
                'max_score' => 13,
                'weight' => 0.2,
            ],
            'budget_and_expenses' => [
                'label' => 'Budget and Expenses',
                'score' => $budgetScore,
                'max_score' => 7,
                'weight' => 0.2,
            ],
            'severity_of_crisis' => [
                'label' => 'Severity of Crisis',
                'score' => $severityScore,
                'max_score' => 8,
                'weight' => 0.1,
            ],
            'availability_of_support_systems' => [
                'label' => 'Availability of Support Systems',
                'score' => $supportScore,
                'max_score' => 20,
                'weight' => 0.1,
            ],
            'external_resources_tapped' => [
                'label' => 'External Resources Tapped',
                'score' => $externalScore,
                'max_score' => 28,
                'weight' => 0.2,
            ],
            'self_help_and_client_efforts' => [
                'label' => 'Self-Help and Client Efforts',
                'score' => $selfHelpScore,
                'max_score' => 8,
                'weight' => 0.1,
            ],
            'vulnerability_and_risk_factors' => [
                'label' => 'Vulnerability and Risk Factors',
                'score' => $vulnerabilityScore,
                'max_score' => 12,
                'weight' => 0.1,
            ],
        ];

        $breakdown = collect($breakdown)->map(function (array $row) {
            $row['weighted_score'] = ($row['score'] / $row['max_score']) * $row['weight'];

            return $row;
        })->all();

        $score = collect($breakdown)->sum('score');
        $percentage = $score / 96;
        $recommendedAmount = $amountNeeded * 0.75 * min(1, $percentage);

        return [
            'amount_needed' => round($amountNeeded, 2),
            'score' => round($score, 2),
            'max_score' => 96,
            'percentage_equivalent' => round($percentage, 4),
            'recommended_amount' => round($recommendedAmount, 2),
            'breakdown' => $breakdown,
        ];
    }

    protected function containsAny(array $items, array $needles): bool
    {
        return collect($items)->contains(function ($item) use ($needles) {
            return in_array((string) $item, $needles, true);
        });
    }

    protected function hasAffirmativeText(mixed $value): bool
    {
        $text = Str::lower(trim((string) $value));

        return $text !== ''
            && ! in_array($text, ['no', 'none', 'n/a', 'na', 'not applicable'], true);
    }

    protected function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'recommended_amount' => [
                    'type' => 'number',
                    'description' => 'Recommended amount in Philippine pesos.',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'Short rationale for the recommendation.',
                ],
                'confidence' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                    'description' => 'Confidence score from 0 to 100.',
                ],
                'score' => [
                    'type' => 'number',
                    'description' => 'Total estimator score out of 96.',
                ],
                'percentage_equivalent' => [
                    'type' => 'number',
                    'description' => 'Estimator score divided by 96.',
                ],
            ],
            'required' => ['recommended_amount', 'summary', 'confidence', 'score', 'percentage_equivalent'],
        ];
    }
}
