<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProviderBankAccount extends Model
{
    protected $fillable = [
        'service_provider_id',
        'bank_id',
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'service_provider_bank_account_id');
    }

    public function resolvedBankName(): ?string
    {
        return $this->bank?->name ?: $this->bank_name;
    }

    public function maskedAccountNumber(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->account_number);

        if ($digits === '') {
            return 'No account number';
        }

        return '****'.substr($digits, -4);
    }

    public function displayLabel(): string
    {
        return trim(collect([
            $this->resolvedBankName(),
            $this->account_name,
            $this->maskedAccountNumber(),
        ])->filter()->implode(' • '));
    }
}
