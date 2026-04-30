<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssistanceDocumentRequirement extends Model
{
    protected $fillable = [
        'assistance_subtype_id',
        'assistance_detail_id',
        'name',
        'description',
        'is_required',
        'applies_when_amount_exceeds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'applies_when_amount_exceeds' => 'decimal:2',
    ];

    public function subtype()
    {
        return $this->belongsTo(AssistanceSubtype::class, 'assistance_subtype_id');
    }

    public function detail()
    {
        return $this->belongsTo(AssistanceDetail::class, 'assistance_detail_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSelection(Builder $query, int $subtypeId, ?int $detailId = null): Builder
    {
        return $query->where('assistance_subtype_id', $subtypeId)
            ->where(function (Builder $inner) use ($detailId) {
                $inner->whereNull('assistance_detail_id');

                if ($detailId) {
                    $inner->orWhere('assistance_detail_id', $detailId);
                }
            });
    }

    public function appliesToAmount(?float $amount): bool
    {
        if ($this->applies_when_amount_exceeds === null) {
            return true;
        }

        if ($amount === null) {
            return false;
        }

        return $amount > (float) $this->applies_when_amount_exceeds;
    }

    public function isRequiredForAmount(?float $amount): bool
    {
        return $this->is_required && $this->appliesToAmount($amount);
    }
}
