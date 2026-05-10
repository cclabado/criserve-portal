<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralInstitution extends Model
{
    protected $fillable = [
        'name',
        'addressee',
        'address',
        'email',
        'contact_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(User::class)->orderBy('name');
    }

    public function referrals()
    {
        return $this->hasMany(ApplicationAssistanceRecommendation::class)->orderByDesc('updated_at');
    }

    public function institutionReferrals()
    {
        return $this->hasMany(InstitutionReferral::class)->orderByDesc('submitted_at');
    }
}
