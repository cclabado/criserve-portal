<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceSubtype extends Model
{
    protected $fillable = ['assistance_type_id', 'name'];

    public function type()
    {
        return $this->belongsTo(AssistanceType::class);
    }
}
