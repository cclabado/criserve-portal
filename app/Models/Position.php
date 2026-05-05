<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'name',
        'position_code',
        'salary_grade',
        'requires_license_number',
        'is_active',
    ];

    protected $casts = [
        'salary_grade' => 'integer',
        'requires_license_number' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
