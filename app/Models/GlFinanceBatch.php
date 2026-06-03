<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlFinanceBatch extends Model
{
    protected $fillable = [
        'batch_no',
        'service_provider_id',
        'service_provider_bank_account_id',
        'finance_fund_source_name',
        'status',
        'current_stage',
        'total_amount',
        'application_count',
        'compliance_trigger_application_id',
        'decision_notes',
        'created_by_user_id',
        'batched_by_user_id',
        'submitted_at',
        'completed_at',
        'fund_cluster',
        'responsibility_center',
        'mfo_pap',
        'mode_of_payment',
        'payee_tin',
        'ors_number',
        'ors_date',
        'dv_number',
        'dv_date',
        'lddap_ada_number',
        'lddap_ada_date',
        'nca_number',
        'nca_date',
        'servicing_bank_branch',
        'mds_sub_account_number',
        'withholding_tax_amount',
        'program_approval_status',
        'program_approval_remarks',
        'program_approved_by',
        'program_approved_at',
        'budget_approval_status',
        'budget_approval_remarks',
        'budget_approved_by',
        'budget_approved_at',
        'accounting_approval_status',
        'accounting_approval_remarks',
        'accounting_approved_by',
        'accounting_approved_at',
        'program_amount_approval_status',
        'program_amount_approval_remarks',
        'program_amount_approved_by',
        'program_amount_approved_at',
        'cash_approval_status',
        'cash_approval_remarks',
        'cash_approved_by',
        'cash_approved_at',
        'accounting_certification_status',
        'accounting_certification_remarks',
        'accounting_certified_by',
        'accounting_certified_at',
        'finance_director_status',
        'finance_director_remarks',
        'finance_director_approved_by',
        'finance_director_approved_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'application_count' => 'integer',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'ors_date' => 'date',
        'dv_date' => 'date',
        'lddap_ada_date' => 'date',
        'nca_date' => 'date',
        'withholding_tax_amount' => 'decimal:2',
        'program_approved_at' => 'datetime',
        'budget_approved_at' => 'datetime',
        'accounting_approved_at' => 'datetime',
        'program_amount_approved_at' => 'datetime',
        'cash_approved_at' => 'datetime',
        'accounting_certified_at' => 'datetime',
        'finance_director_approved_at' => 'datetime',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(ServiceProviderBankAccount::class, 'service_provider_bank_account_id');
    }

    public function items()
    {
        return $this->hasMany(GlFinanceBatchItem::class)->orderBy('sequence_no');
    }

    public function applications()
    {
        return $this->belongsToMany(Application::class, 'gl_finance_batch_items')
            ->using(GlFinanceBatchItem::class)
            ->withPivot(['id', 'sequence_no', 'utilized_amount', 'item_status', 'flagged_for_compliance', 'flag_reason'])
            ->withTimestamps()
            ->orderBy('gl_finance_batch_items.sequence_no');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function batchedBy()
    {
        return $this->belongsTo(User::class, 'batched_by_user_id');
    }

    public function programApprover()
    {
        return $this->belongsTo(User::class, 'program_approved_by');
    }

    public function complianceTriggerApplication()
    {
        return $this->belongsTo(Application::class, 'compliance_trigger_application_id');
    }

    public static function nextBatchNo(): string
    {
        $today = now();
        $prefix = 'GLB-'.$today->format('Ymd');

        $latest = static::query()
            ->where('batch_no', 'like', $prefix.'-%')
            ->latest('id')
            ->value('batch_no');

        $nextNumber = 1;

        if ($latest && preg_match('/-(\d{4})$/', $latest, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $nextNumber);
    }
}
