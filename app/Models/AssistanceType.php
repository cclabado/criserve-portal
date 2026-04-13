<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceType extends Model
{
    protected $fillable = ['name'];

    public function subtypes()
    {
        return $this->hasMany(AssistanceSubtype::class);
    }
}
