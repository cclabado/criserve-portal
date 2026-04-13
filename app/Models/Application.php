<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'reference_no', 
        'assistance_type_id',
        'assistance_subtype_id',
        'mode_of_assistance',
        'status'
    ];
}
