<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeOfAssistance extends Model
{
    protected $fillable = ['name', 'minimum_amount', 'maximum_amount', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
