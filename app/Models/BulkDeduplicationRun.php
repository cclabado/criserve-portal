<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkDeduplicationRun extends Model
{
    protected $fillable = [
        'user_id',
        'access_role',
        'original_filename',
        'upload_disk',
        'upload_path',
        'reference_upload_disk',
        'reference_upload_path',
        'status',
        'progress_percentage',
        'progress_message',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
        'summary',
        'clean_rows',
        'duplicate_rows',
        'finding_rows',
        'skipped_rows',
    ];

    protected $casts = [
        'summary' => 'array',
        'clean_rows' => 'array',
        'duplicate_rows' => 'array',
        'finding_rows' => 'array',
        'skipped_rows' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
