<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'social_worker_id',
        'reference_no', 
        'assistance_type_id',
        'assistance_subtype_id',
        'mode_of_assistance',
        'notes',
        'schedule_date',
        'meeting_link',
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
        'ai_recommendation_generated_at' => 'datetime',
    ];
    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class, 'assistance_type_id');
    }

    public function assistanceSubtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function socialWorker()
    {
        return $this->belongsTo(User::class, 'social_worker_id');
    }

    public function beneficiary()
    {
        return $this->hasOne(Beneficiary::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
    
