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
}
