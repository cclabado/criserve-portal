<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    protected $fillable = [
        'application_id',
        'last_name',
        'first_name',
        'middle_name',
        'relationship',
        'birthdate'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
    public function relationshipData()
    {
        return $this->belongsTo(\App\Models\Relationship::class, 'relationship', 'id');
    }
}