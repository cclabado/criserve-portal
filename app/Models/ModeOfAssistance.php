<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeOfAssistance extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
