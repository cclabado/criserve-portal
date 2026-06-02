<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationReferenceCounter extends Model
{
    protected $fillable = [
        'reference_year',
        'reference_month',
        'last_number',
    ];
}
