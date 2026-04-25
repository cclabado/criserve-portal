<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceDetail extends Model
{
    protected $fillable = ['assistance_subtype_id', 'name'];

    public function subtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }

    public function frequencyRule()
    {
        return $this->hasOne(AssistanceFrequencyRule::class, 'assistance_detail_id');
    }
}
