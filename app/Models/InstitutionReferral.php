<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionReferral extends Model
{
    protected $fillable = [
        'referral_institution_id',
        'referred_by_user_id',
        'application_id',
        'subject_type',
        'client_last_name',
        'client_first_name',
        'client_middle_name',
        'client_extension_name',
        'client_birthdate',
        'client_contact_number',
        'client_address',
        'beneficiary_last_name',
        'beneficiary_first_name',
        'beneficiary_middle_name',
        'beneficiary_extension_name',
        'beneficiary_birthdate',
        'beneficiary_contact_number',
        'beneficiary_address',
        'requested_assistance',
        'case_summary',
        'institution_notes',
        'officer_notes',
        'status',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'client_birthdate' => 'date',
            'beneficiary_birthdate' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(ReferralInstitution::class, 'referral_institution_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }
}
