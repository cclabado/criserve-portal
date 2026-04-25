<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'client_id',
        'beneficiary_profile_id',
        'user_id',
        'social_worker_id',
        'reference_no', 
        'assistance_type_id',
        'assistance_subtype_id',
        'assistance_detail_id',
        'mode_of_assistance_id',
        'frequency_rule_id',
        'frequency_basis_application_id',
        'mode_of_assistance',
        'frequency_status',
        'frequency_message',
        'frequency_reference_date',
        'frequency_case_key',
        'frequency_exception_reason',
        'frequency_override_reason',
        'frequency_checked_at',
        'notes',
        'schedule_date',
        'meeting_link',
        'google_calendar_event_id',
        'google_calendar_event_link',
        'status',
        'monthly_income',
        'household_members',
        'working_members',
        'monthly_expenses',
        'savings',
        'crisis_type',
        'urgency_level',
        'has_elderly',
        'has_child',
        'has_pwd',
        'has_pregnant',
        'earner_unable_to_work',
        'has_philhealth',
        'has_family_support',
        'recommended_amount',
        'final_amount',
        'problem_statement',
        'social_worker_assessment',
        'ai_recommendation_summary',
        'ai_recommendation_confidence',
        'ai_recommendation_source',
        'ai_recommendation_model',
        'ai_recommendation_generated_at',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'monthly_expenses' => 'decimal:2',
        'savings' => 'decimal:2',
        'recommended_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'has_elderly' => 'boolean',
        'has_child' => 'boolean',
        'has_pwd' => 'boolean',
        'has_pregnant' => 'boolean',
        'earner_unable_to_work' => 'boolean',
        'has_philhealth' => 'boolean',
        'has_family_support' => 'boolean',
        'schedule_date' => 'datetime',
        'ai_recommendation_generated_at' => 'datetime',
        'frequency_reference_date' => 'date',
        'frequency_checked_at' => 'datetime',
    ];
    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class, 'assistance_type_id');
    }

    public function assistanceSubtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }

    public function assistanceDetail()
    {
        return $this->belongsTo(AssistanceDetail::class, 'assistance_detail_id');
    }

    public function modeOfAssistance()
    {
        return $this->belongsTo(ModeOfAssistance::class, 'mode_of_assistance_id');
    }

    public function frequencyRule()
    {
        return $this->belongsTo(AssistanceFrequencyRule::class, 'frequency_rule_id');
    }

    public function frequencyBasisApplication()
    {
        return $this->belongsTo(self::class, 'frequency_basis_application_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function socialWorker()
    {
        return $this->belongsTo(User::class, 'social_worker_id');
    }

    public function beneficiaryProfile()
    {
        return $this->belongsTo(BeneficiaryProfile::class);
    }

    public function beneficiary()
    {
        return $this->hasOne(Beneficiary::class);
    }

    public function usesBeneficiaryHousehold(): bool
    {
        return ! is_null($this->beneficiary_profile_id);
    }

    public function householdProfileLabel(): string
    {
        if ($this->usesBeneficiaryHousehold()) {
            $beneficiaryName = trim(implode(' ', array_filter([
                $this->beneficiary?->first_name,
                $this->beneficiary?->middle_name,
                $this->beneficiary?->last_name,
                $this->beneficiary?->extension_name,
            ])));

            return $beneficiaryName !== ''
                ? 'Beneficiary Household - '.$beneficiaryName
                : 'Beneficiary Household';
        }

        $clientName = trim(implode(' ', array_filter([
            $this->client?->first_name,
            $this->client?->middle_name,
            $this->client?->last_name,
            $this->client?->extension_name,
        ])));

        return $clientName !== ''
            ? 'Client Household - '.$clientName
            : 'Client Household';
    }

    public function familyMembers()
    {
        if ($this->beneficiary_profile_id) {
            return $this->hasMany(FamilyMember::class, 'beneficiary_profile_id', 'beneficiary_profile_id')->orderBy('id');
        }

        return $this->hasMany(FamilyMember::class, 'client_id', 'client_id')
            ->whereNull('beneficiary_profile_id')
            ->orderBy('id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
    
