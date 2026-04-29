<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationAssistanceRecommendation extends Model
{
    protected $fillable = [
        'application_id',
        'assistance_type_id',
        'assistance_subtype_id',
        'assistance_detail_id',
        'mode_of_assistance_id',
        'referral_institution_id',
        'frequency_rule_id',
        'frequency_basis_application_id',
        'recommended_amount',
        'final_amount',
        'frequency_status',
        'frequency_message',
        'frequency_case_key',
        'frequency_override_reason',
        'frequency_checked_at',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'recommended_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'frequency_checked_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class);
    }

    public function assistanceSubtype()
    {
        return $this->belongsTo(AssistanceSubtype::class);
    }

    public function assistanceDetail()
    {
        return $this->belongsTo(AssistanceDetail::class);
    }

    public function modeOfAssistance()
    {
        return $this->belongsTo(ModeOfAssistance::class);
    }

    public function referralInstitution()
    {
        return $this->belongsTo(ReferralInstitution::class);
    }

    public function frequencyRule()
    {
        return $this->belongsTo(AssistanceFrequencyRule::class);
    }

    public function frequencyBasisApplication()
    {
        return $this->belongsTo(Application::class, 'frequency_basis_application_id');
    }
}
