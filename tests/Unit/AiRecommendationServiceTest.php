<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Beneficiary;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Services\AiRecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiRecommendationServiceTest extends TestCase
{
    public function test_it_returns_fallback_when_api_key_is_missing(): void
    {
        Config::set('services.openai.api_key', null);

        $service = new AiRecommendationService();
        $result = $service->generate($this->makeApplication(), $this->sampleIntakeData());

        $this->assertSame('fallback_rules', $result['source']);
        $this->assertSame(15000, $result['recommended_amount']);
        $this->assertStringContainsString('Rule-based fallback used.', $result['summary']);
    }

    public function test_it_uses_openai_response_when_available(): void
    {
        Config::set('services.openai.api_key', 'test-key');
        Config::set('services.openai.base_url', 'https://api.openai.com/v1');
        Config::set('services.openai.model', 'gpt-5.2');

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'model' => 'gpt-5.2',
                'output_text' => json_encode([
                    'recommended_amount' => 12500,
                    'summary' => 'Urgent hospitalization and no savings justify higher aid.',
                    'confidence' => 84,
                ]),
            ]),
        ]);

        $service = new AiRecommendationService();
        $result = $service->generate($this->makeApplication(), $this->sampleIntakeData());

        $this->assertSame('openai', $result['source']);
        $this->assertSame(12500.0, $result['recommended_amount']);
        $this->assertSame('Urgent hospitalization and no savings justify higher aid.', $result['summary']);
        $this->assertSame(84, $result['confidence']);
        $this->assertSame('gpt-5.2', $result['model']);
    }

    protected function makeApplication(): Application
    {
        $application = new Application([
            'reference_no' => 'REF-001',
            'mode_of_assistance' => 'Cash',
        ]);

        $application->setRelation('client', new Client([
            'first_name' => 'Ana',
            'last_name' => 'Dela Cruz',
            'full_address' => 'Sample Address',
            'civil_status' => 'Married',
        ]));
        $application->setRelation('beneficiary', new Beneficiary());
        $application->setRelation('familyMembers', new Collection([
            new FamilyMember(),
            new FamilyMember(),
        ]));
        $application->setRelation('assistanceType', new AssistanceType(['name' => 'Medical']));
        $application->setRelation('assistanceSubtype', new AssistanceSubtype(['name' => 'Hospital Bill']));

        return $application;
    }

    protected function sampleIntakeData(): array
    {
        return [
            'monthly_income' => 8000,
            'household_members' => 5,
            'working_members' => 1,
            'monthly_expenses' => 12000,
            'savings' => 0,
            'crisis_type' => 'Hospitalization',
            'urgency_level' => 'Critical',
            'has_elderly' => true,
            'has_child' => true,
            'has_pwd' => false,
            'has_pregnant' => false,
            'earner_unable_to_work' => true,
            'has_philhealth' => false,
            'has_family_support' => false,
        ];
    }
}
