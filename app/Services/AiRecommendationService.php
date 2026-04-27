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
        if (! config('services.openai.api_key')) {
            return $this->fallback($intakeData, 'OpenAI API key is not configured.');
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
                            'text' => $this->userPrompt($application, $intakeData),
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

            return $this->normalizeResponse($response->json());
        } catch (ConnectionException $exception) {
            return $this->fallback(
                $intakeData,
                'AI service connection failed. A fallback recommendation was used instead.'
            );
        } catch (RequestException $exception) {
            return $this->fallback(
                $intakeData,
                'AI request failed: '.Str::limit($exception->getMessage(), 180)
            );
        } catch (RuntimeException $exception) {
            return $this->fallback(
                $intakeData,
                'AI response could not be parsed: '.Str::limit($exception->getMessage(), 180)
            );
        }
    }

    public function fallback(array $intakeData, ?string $reason = null): array
    {
        $income = (float) ($intakeData['monthly_income'] ?? 0);
        $expenses = (float) ($intakeData['monthly_expenses'] ?? 0);
        $savings = (float) ($intakeData['savings'] ?? 0);
        $urgency = $intakeData['urgency_level'] ?? null;
        $crisis = $intakeData['crisis_type'] ?? null;

        $score = 0;

        if ($income < 10000) {
            $score += 3;
        } elseif ($income < 20000) {
            $score += 2;
        }

        if ($expenses > $income) {
            $score += 2;
        }

        if ($savings <= 0) {
            $score += 1;
        }

        $score += match ($urgency) {
            'Critical' => 4,
            'High' => 3,
            'Medium' => 2,
            default => 0,
        };

        if (in_array($crisis, ['Hospitalization', 'Death', 'Disaster'], true)) {
            $score += 3;
        }

        foreach (['has_elderly', 'has_child', 'has_pwd', 'has_pregnant'] as $flag) {
            if (! empty($intakeData[$flag])) {
                $score += 1;
            }
        }

        if (! empty($intakeData['earner_unable_to_work'])) {
            $score += 2;
        }

        if (empty($intakeData['has_family_support'])) {
            $score += 2;
        }

        $amount = match (true) {
            $score <= 3 => 3000,
            $score <= 6 => 5000,
            $score <= 9 => 8000,
            $score <= 12 => 10000,
            default => 15000,
        };

        $summary = collect([
            'Rule-based fallback used.',
            $urgency ? "Urgency level: {$urgency}." : null,
            $crisis ? "Crisis type: {$crisis}." : null,
            $expenses > $income ? 'Monthly expenses exceed monthly income.' : null,
            $savings <= 0 ? 'No available savings were reported.' : null,
            empty($intakeData['has_family_support']) ? 'No family support was reported.' : null,
            $reason,
        ])->filter()->implode(' ');

        return [
            'recommended_amount' => $amount,
            'summary' => $summary,
            'confidence' => 55,
            'source' => 'fallback_rules',
            'model' => null,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function normalizeResponse(array $payload): array
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
            'recommended_amount' => max(0, (float) Arr::get($decoded, 'recommended_amount', 0)),
            'summary' => trim((string) Arr::get($decoded, 'summary', '')),
            'confidence' => min(100, max(0, (int) Arr::get($decoded, 'confidence', 0))),
            'source' => 'openai',
            'model' => Arr::get($payload, 'model', config('services.openai.model')),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are assisting a municipal social worker who is screening emergency aid requests.
Return a conservative recommendation in Philippine pesos based only on the provided case data.
Do not invent facts. Keep the summary to 2-4 sentences and mention the strongest drivers for the recommendation.
PROMPT;
    }

    protected function userPrompt(Application $application, array $intakeData): string
    {
        $caseData = [
            'reference_no' => $application->reference_no,
            'assistance_type' => optional($application->assistanceType)->name,
            'assistance_subtype' => optional($application->assistanceSubtype)->name,
            'mode_of_assistance' => $application->mode_of_assistance,
            'client' => [
                'name' => trim(($application->client->first_name ?? '').' '.($application->client->last_name ?? '')),
                'address' => $application->client->full_address ?? null,
                'civil_status' => $application->client->civil_status ?? null,
            ],
            'beneficiary_present' => $application->beneficiary !== null,
            'family_members_count' => $application->familyMembers->count(),
            'intake' => $intakeData,
        ];

        return "Case data:\n".json_encode($caseData, JSON_PRETTY_PRINT);
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
            ],
            'required' => ['recommended_amount', 'summary', 'confidence'],
        ];
    }
}
