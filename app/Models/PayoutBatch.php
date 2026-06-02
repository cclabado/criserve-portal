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
        'import_status',
        'processed_rows',
        'progress_message',
        'error_message',
        'import_started_at',
        'import_completed_at',
        'import_failed_at',
        'summary',
        'notes',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'payout_amount' => 'decimal:2',
        'processed_rows' => 'integer',
        'summary' => 'array',
        'is_active' => 'boolean',
        'import_started_at' => 'datetime',
        'import_completed_at' => 'datetime',
        'import_failed_at' => 'datetime',
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

    public function isImportComplete(): bool
    {
        return $this->import_status === 'completed';
    }
}
