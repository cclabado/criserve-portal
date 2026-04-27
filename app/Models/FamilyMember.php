<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    protected $fillable = [
        'application_id',
        'client_id',
        'linked_user_id',
        'person_id',
        'beneficiary_profile_id',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'relationship',
        'birthdate'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function linkedUser()
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function beneficiaryProfile()
    {
        return $this->belongsTo(BeneficiaryProfile::class);
    }

    public function relationshipData()
    {
        return $this->belongsTo(\App\Models\Relationship::class, 'relationship', 'id');
    }
}
