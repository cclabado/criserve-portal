<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'application_id',
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
}