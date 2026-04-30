<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceSubtype extends Model
{
    protected $fillable = ['assistance_type_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(AssistanceType::class);
    }

    public function details()
    {
        return $this->hasMany(AssistanceDetail::class)->orderBy('name');
    }

    public function frequencyRule()
    {
        return $this->hasOne(AssistanceFrequencyRule::class)->whereNull('assistance_detail_id');
    }

    public function documentRequirements()
    {
        return $this->hasMany(AssistanceDocumentRequirement::class)
            ->whereNull('assistance_detail_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
