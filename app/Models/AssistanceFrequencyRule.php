<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceFrequencyRule extends Model
{
    protected $fillable = [
        'assistance_subtype_id',
        'assistance_detail_id',
        'rule_type',
        'interval_months',
        'requires_reference_date',
        'requires_case_key',
        'allows_exception_request',
        'notes',
    ];

    protected $casts = [
        'requires_reference_date' => 'boolean',
        'requires_case_key' => 'boolean',
        'allows_exception_request' => 'boolean',
    ];

    public function subtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }

    public function detail()
    {
        return $this->belongsTo(AssistanceDetail::class, 'assistance_detail_id');
    }
}
