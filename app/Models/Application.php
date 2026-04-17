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
        'notes',
        'schedule_date',
        'meeting_link',
        'status'
    ];
    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class, 'assistance_type_id');
    }

    public function assistanceSubtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function beneficiary()
    {
        return $this->hasOne(Beneficiary::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
    