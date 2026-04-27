<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'sex',
        'birthdate',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function beneficiaryProfiles()
    {
        return $this->hasMany(BeneficiaryProfile::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function relationships()
    {
        return $this->hasMany(PersonRelationship::class);
    }

    public function relatedTo()
    {
        return $this->hasMany(PersonRelationship::class, 'related_person_id');
    }

    public function displayName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->extension_name,
        ])));
    }
}
