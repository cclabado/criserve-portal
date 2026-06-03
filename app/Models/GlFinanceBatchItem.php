<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GlFinanceBatchItem extends Pivot
{
    protected $table = 'gl_finance_batch_items';

    protected $fillable = [
        'gl_finance_batch_id',
        'application_id',
        'sequence_no',
        'utilized_amount',
        'item_status',
        'flagged_for_compliance',
        'flag_reason',
    ];

    protected $casts = [
        'utilized_amount' => 'decimal:2',
        'flagged_for_compliance' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(GlFinanceBatch::class, 'gl_finance_batch_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
