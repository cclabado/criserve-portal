<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'application_id',
        'beneficiary_profile_id',
        'person_id',
        'relationship_id',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'sex',
        'birthdate',
        'contact_number',
        'full_address'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function beneficiaryProfile()
    {
        return $this->belongsTo(BeneficiaryProfile::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function relationshipData()
    {
        return $this->belongsTo(Relationship::class, 'relationship_id');
    }
}
