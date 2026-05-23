<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    protected $fillable = [
        'user_id',
        'activated_by_user_id',
        'bulk_deduplication_run_id',
        'access_role',
        'batch_name',
        'sector_label',
        'venue',
        'payout_amount',
        'payout_date',
        'source_filename',
        'upload_disk',
        'upload_path',
        'summary',
        'notes',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'payout_amount' => 'decimal:2',
        'summary' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by_user_id');
    }

    public function bulkDeduplicationRun()
    {
        return $this->belongsTo(BulkDeduplicationRun::class);
    }

    public function entries()
    {
        return $this->hasMany(PayoutEntry::class);
    }
}
