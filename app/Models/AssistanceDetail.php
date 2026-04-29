<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceDetail extends Model
{
    protected $fillable = ['assistance_subtype_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }

    public function frequencyRule()
    {
        return $this->hasOne(AssistanceFrequencyRule::class, 'assistance_detail_id');
    }
}
