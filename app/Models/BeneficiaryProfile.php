<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeneficiaryProfile extends Model
{
    protected $fillable = [
        'client_id',
        'linked_user_id',
        'person_id',
        'relationship_id',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'sex',
        'birthdate',
        'contact_number',
        'full_address',
    ];

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

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class)->orderBy('id');
    }
}
