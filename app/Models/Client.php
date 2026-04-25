<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'contact_number',
        'birthdate',
        'sex',
        'civil_status',
        'full_address'
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class)->orderBy('id');
    }

    public function beneficiaryProfiles()
    {
        return $this->hasMany(BeneficiaryProfile::class)->orderBy('id');
    }
}
