<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'application_id',
        'service_provider_bank_account_id',
        'document_requirement_id',
        'document_type',
        'file_name',
        'file_path',
        'storage_disk',
        'mime_type',
        'file_size',
        'file_hash',
        'remarks',
        'bank_name_snapshot',
        'account_name_snapshot',
        'account_number_snapshot',
        'branch_name_snapshot',
        'requires_client_resubmission',
    ];

    protected $casts = [
        'requires_client_resubmission' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement()
    {
        return $this->belongsTo(AssistanceDocumentRequirement::class, 'document_requirement_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(ServiceProviderBankAccount::class, 'service_provider_bank_account_id');
    }

    public function maskedBankAccountNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->account_number_snapshot);

        if ($digits === '') {
            return null;
        }

        return '****'.substr($digits, -4);
    }

    public function bankAccountSummary(): ?string
    {
        if (blank($this->bank_name_snapshot) && blank($this->account_name_snapshot) && blank($this->account_number_snapshot)) {
            return null;
        }

        return trim(collect([
            $this->bank_name_snapshot,
            $this->account_name_snapshot,
            $this->maskedBankAccountNumber(),
        ])->filter()->implode(' • '));
    }
}
