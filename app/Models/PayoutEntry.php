<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutEntry extends Model
{
    protected $fillable = [
        'payout_batch_id',
        'sequence_no',
        'reference_no',
        'full_name',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'birthdate',
        'sector_label',
        'assistance_subtype',
        'assistance_detail',
        'payout_status',
        'paid_at',
        'paid_by_user_id',
        'handling_by_user_id',
        'handling_started_at',
        'proof_photo_disk',
        'proof_photo_path',
        'proof_photo_mime_type',
        'remarks',
        'payout_notes',
        'raw_row',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'paid_at' => 'datetime',
        'handling_started_at' => 'datetime',
        'raw_row' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(PayoutBatch::class, 'payout_batch_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function handlingUser()
    {
        return $this->belongsTo(User::class, 'handling_by_user_id');
    }

    public function hasProofPhoto(): bool
    {
        return filled($this->proof_photo_disk) && filled($this->proof_photo_path);
    }

    public function isHandlingLockActive(int $minutes = 15): bool
    {
        return $this->handling_by_user_id !== null
            && $this->handling_started_at !== null
            && $this->handling_started_at->gt(now()->subMinutes($minutes));
    }
}
