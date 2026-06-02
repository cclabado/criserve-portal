<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Application extends Model
{
    public static function nextReferenceNo(): string
    {
        return DB::transaction(function () {
            $now = now();
            $year = (int) $now->format('Y');
            $month = (int) $now->format('m');

            $counter = ApplicationReferenceCounter::query()
                ->where('reference_year', $year)
                ->where('reference_month', $month)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = ApplicationReferenceCounter::create([
                    'reference_year' => $year,
                    'reference_month' => $month,
                    'last_number' => 0,
                ]);

                $counter->refresh();
            }

            $counter->increment('last_number');
            $counter->refresh();

            return sprintf('APP-%04d-%02d-%06d', $year, $month, (int) $counter->last_number);
        }, 3);
    }

    public static function effectiveDisplayedAmountSql(string $table = 'applications'): string
    {
        return "COALESCE({$table}.gl_actual_utilized_amount, {$table}.final_amount, {$table}.recommended_amount, {$table}.amount_needed, 0)";
    }

    protected $fillable = [
        'client_id',
        'beneficiary_profile_id',
        'user_id',
        'social_worker_id',
        'approving_officer_id',
        'reference_no',
        'assistance_type_id',
        'assistance_subtype_id',
        'assistance_detail_id',
        'mode_of_assistance_id',
        'service_provider_id',
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
        'gis_client_type',
        'gis_visit_type',
        'diagnosis_or_cause_of_death',
        'occupation_sources',
        'insurance_coverage',
        'emergency_fund',
        'disease_duration',
        'experienced_recent_crisis',
        'recent_crisis_types',
        'support_systems',
        'external_resources',
        'self_help_efforts',
        'client_sector',
        'client_sectors',
        'client_sub_category',
        'client_sub_categories',
        'disability_type',
        'disability_types',
        'total_income_past_six_months',
        'income_sources',
        'google_calendar_event_id',
        'google_calendar_event_link',
        'status',
        'client_compliance_status',
        'client_compliance_notes',
        'client_compliance_requested_at',
        'client_compliance_responded_at',
        'monthly_income',
        'household_members',
        'working_members',
        'seasonal_worker_members',
        'has_insurance_coverage',
        'has_savings',
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
        'has_vulnerable_household_member',
        'has_unstable_employment',
        'amount_needed',
        'recommended_amount',
        'final_amount',
        'gl_actual_utilized_amount',
        'gl_payment_status',
        'gl_soa_status',
        'gl_soa_review_notes',
        'gl_soa_reviewed_by',
        'gl_soa_reviewed_at',
        'gl_finance_fund_source',
        'gl_fund_cluster',
        'gl_responsibility_center',
        'gl_mfo_pap',
        'gl_mode_of_payment',
        'gl_payee_tin',
        'gl_ors_number',
        'gl_ors_date',
        'gl_dv_number',
        'gl_dv_date',
        'gl_lddap_ada_number',
        'gl_lddap_ada_date',
        'gl_nca_number',
        'gl_nca_date',
        'gl_servicing_bank_branch',
        'gl_mds_sub_account_number',
        'gl_withholding_tax_amount',
        'gl_budget_remarks',
        'gl_budget_reviewed_by',
        'gl_budget_reviewed_at',
        'gl_budget_approval_status',
        'gl_budget_approval_remarks',
        'gl_budget_approved_by',
        'gl_budget_approved_at',
        'gl_program_approval_status',
        'gl_program_approval_remarks',
        'gl_program_approved_by',
        'gl_program_approved_at',
        'gl_program_amount_approval_status',
        'gl_program_amount_approval_remarks',
        'gl_program_amount_approved_by',
        'gl_program_amount_approved_at',
        'gl_cash_review_status',
        'gl_cash_remarks',
        'gl_cash_reviewed_by',
        'gl_cash_reviewed_at',
        'gl_cash_approval_status',
        'gl_cash_approval_remarks',
        'gl_cash_approved_by',
        'gl_cash_approved_at',
        'gl_cash_certification_status',
        'gl_cash_certification_remarks',
        'gl_cash_certified_by',
        'gl_cash_certified_at',
        'gl_finance_director_status',
        'gl_finance_director_remarks',
        'gl_finance_director_approved_by',
        'gl_finance_director_approved_at',
        'gl_accounting_review_status',
        'gl_accounting_remarks',
        'gl_accounting_reviewed_by',
        'gl_accounting_reviewed_at',
        'gl_accounting_approval_status',
        'gl_accounting_approval_remarks',
        'gl_accounting_approved_by',
        'gl_accounting_approved_at',
        'client_signature_path',
        'client_signature_disk',
        'client_signature_mime_type',
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
        'amount_needed' => 'decimal:2',
        'recommended_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'gl_actual_utilized_amount' => 'decimal:2',
        'gl_soa_reviewed_at' => 'datetime',
        'gl_ors_date' => 'date',
        'gl_dv_date' => 'date',
        'gl_lddap_ada_date' => 'date',
        'gl_nca_date' => 'date',
        'gl_withholding_tax_amount' => 'decimal:2',
        'gl_budget_reviewed_at' => 'datetime',
        'gl_budget_approved_at' => 'datetime',
        'gl_program_approved_at' => 'datetime',
        'gl_program_amount_approved_at' => 'datetime',
        'gl_cash_reviewed_at' => 'datetime',
        'gl_cash_approved_at' => 'datetime',
        'gl_cash_certified_at' => 'datetime',
        'gl_finance_director_approved_at' => 'datetime',
        'gl_accounting_reviewed_at' => 'datetime',
        'gl_accounting_approved_at' => 'datetime',
        'has_elderly' => 'boolean',
        'has_child' => 'boolean',
        'has_pwd' => 'boolean',
        'has_pregnant' => 'boolean',
        'earner_unable_to_work' => 'boolean',
        'has_philhealth' => 'boolean',
        'has_family_support' => 'boolean',
        'has_vulnerable_household_member' => 'boolean',
        'has_unstable_employment' => 'boolean',
        'has_insurance_coverage' => 'boolean',
        'has_savings' => 'boolean',
        'experienced_recent_crisis' => 'boolean',
        'recent_crisis_types' => 'array',
        'support_systems' => 'array',
        'external_resources' => 'array',
        'self_help_efforts' => 'array',
        'client_sectors' => 'array',
        'client_sub_categories' => 'array',
        'disability_types' => 'array',
        'income_sources' => 'array',
        'total_income_past_six_months' => 'decimal:2',
        'schedule_date' => 'datetime',
        'ai_recommendation_generated_at' => 'datetime',
        'frequency_reference_date' => 'date',
        'frequency_checked_at' => 'datetime',
        'client_compliance_requested_at' => 'datetime',
        'client_compliance_responded_at' => 'datetime',
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

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
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

    public function approvingOfficer()
    {
        return $this->belongsTo(User::class, 'approving_officer_id');
    }

    public function glSoaReviewer()
    {
        return $this->belongsTo(User::class, 'gl_soa_reviewed_by');
    }

    public function glBudgetReviewer()
    {
        return $this->belongsTo(User::class, 'gl_budget_reviewed_by');
    }

    public function glBudgetApprover()
    {
        return $this->belongsTo(User::class, 'gl_budget_approved_by');
    }

    public function glProgramApprover()
    {
        return $this->belongsTo(User::class, 'gl_program_approved_by');
    }

    public function glProgramAmountApprover()
    {
        return $this->belongsTo(User::class, 'gl_program_amount_approved_by');
    }

    public function glCashReviewer()
    {
        return $this->belongsTo(User::class, 'gl_cash_reviewed_by');
    }

    public function glCashApprover()
    {
        return $this->belongsTo(User::class, 'gl_cash_approved_by');
    }

    public function glCashCertifier()
    {
        return $this->belongsTo(User::class, 'gl_cash_certified_by');
    }

    public function glFinanceDirectorApprover()
    {
        return $this->belongsTo(User::class, 'gl_finance_director_approved_by');
    }

    public function glAccountingReviewer()
    {
        return $this->belongsTo(User::class, 'gl_accounting_reviewed_by');
    }

    public function glAccountingApprover()
    {
        return $this->belongsTo(User::class, 'gl_accounting_approved_by');
    }

    public function beneficiaryProfile()
    {
        return $this->belongsTo(BeneficiaryProfile::class);
    }

    public function beneficiary()
    {
        return $this->hasOne(Beneficiary::class);
    }

    public function applicationFamilyMembers()
    {
        return $this->hasMany(FamilyMember::class)->orderBy('id');
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
        return $this->hasMany(FamilyMember::class)->orderBy('id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function assistanceRecommendations()
    {
        return $this->hasMany(ApplicationAssistanceRecommendation::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recommendationFinalAmountTotal(): float
    {
        $this->loadMissing('assistanceRecommendations');

        return (float) $this->assistanceRecommendations
            ->sum(fn (ApplicationAssistanceRecommendation $recommendation) => (float) $recommendation->final_amount);
    }

    public function syncFinalAmountFromRecommendations(): void
    {
        $this->final_amount = $this->recommendationFinalAmountTotal();
        $this->save();
    }

    public function approvalRoutingAmount(): float
    {
        $this->loadMissing('assistanceRecommendations');

        if ($this->assistanceRecommendations->isNotEmpty()) {
            return $this->recommendationFinalAmountTotal();
        }

        foreach (['final_amount', 'recommended_amount', 'amount_needed'] as $field) {
            $amount = $this->{$field};

            if ($amount !== null) {
                return (float) $amount;
            }
        }

        return 0.0;
    }

    public function effectiveDisplayedAmount(): float
    {
        foreach (['gl_actual_utilized_amount', 'final_amount', 'recommended_amount', 'amount_needed'] as $field) {
            $amount = $this->{$field};

            if ($amount !== null) {
                return (float) $amount;
            }
        }

        return 0.0;
    }

    public function clientSignatureDataUrl(): ?string
    {
        if (blank($this->client_signature_path) || blank($this->client_signature_disk)) {
            return null;
        }

        $disk = Storage::disk($this->client_signature_disk);

        if (! $disk->exists($this->client_signature_path)) {
            return null;
        }

        $mimeType = $this->client_signature_mime_type ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($disk->get($this->client_signature_path));
    }
}
