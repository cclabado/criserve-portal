<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
use App\Models\GlFinanceBatch;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountingController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function accountingOfficerDashboard(): View
    {
        $baseQuery = $this->accountingOfficerBatchQuery(true);

        return view('accounting.dashboard', [
            'workspace' => 'officer',
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_processor_remarks' => (clone $baseQuery)->whereHas('applications', fn ($query) => $query->whereNotNull('gl_budget_remarks')->where('gl_budget_remarks', '!=', ''))->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('applications.documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum('total_amount')),
            ],
            'recentCases' => (clone $baseQuery)->latest('updated_at')->take(6)->get(),
            'providerLoad' => $this->batchProviderLoadRows($baseQuery, 5),
        ]);
    }

    public function accountingApproverDashboard(): View
    {
        $baseQuery = $this->accountingApproverBaseQuery(true);
        $cashCertificationQuery = $this->cashCertificationBaseQuery(true);
        $amountSql = Application::effectiveDisplayedAmountSql();

        return view('accounting.dashboard', [
            'workspace' => 'approver',
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_accounting_remarks' => (clone $baseQuery)->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', '')->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum(DB::raw($amountSql))),
                'cash_certifications_total' => (clone $cashCertificationQuery)->count(),
            ],
            'recentCases' => (clone $baseQuery)->latest('gl_accounting_reviewed_at')->latest('updated_at')->take(6)->get(),
            'cashCertificationCases' => (clone $cashCertificationQuery)->latest('gl_cash_approved_at')->latest('updated_at')->take(6)->get(),
            'providerLoad' => $this->providerLoadRows($baseQuery, 5),
        ]);
    }

    public function accountingOfficerQueue(Request $request): View
    {
        return $this->accountingOfficerBatches($request);
    }

    public function accountingApproverQueue(Request $request): View
    {
        return $this->accountingApprovalBatches($request);
    }

    public function cashCertificationQueue(Request $request): View
    {
        return $this->cashCertificationBatches($request);
    }

    public function showAccountingOfficer($id): View
    {
        $batch = $this->accountingOfficerBatchQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('accounting.officer-batch-show', [
            'batch' => $batch,
        ]);
    }

    public function showAccountingOfficerBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->accountingOfficerBatchQuery(true)->findOrFail($batchId);
        $application = $batch->applications()
            ->whereKey($applicationId)
            ->with([
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'assistanceRecommendations.assistanceType',
                'assistanceRecommendations.assistanceSubtype',
                'assistanceRecommendations.assistanceDetail',
                'assistanceRecommendations.modeOfAssistance',
                'serviceProvider',
                'modeOfAssistance',
                'documents',
            ])
            ->firstOrFail();

        return view('accounting.show', [
            'workspace' => 'officer',
            'application' => $application,
            'statementDocuments' => $application->documents
                ->where('document_type', 'Updated Statement of Account')
                ->sortByDesc('created_at')
                ->values(),
            'supportingDocuments' => $application->documents
                ->where('document_type', 'Other Supporting Document')
                ->sortByDesc('created_at')
                ->values(),
            'batch' => $batch,
            'batchBackUrl' => route('accounting-officer.gl-payment-reviews.show', $batch->id),
            'batchBackText' => 'Back to Accounting Review Batch',
        ]);
    }

    public function showAccountingApprover($id): View
    {
        $batch = $this->accountingApprovalBatchQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('accounting.batch-show', [
            'workspace' => 'approver',
            'batch' => $batch,
        ]);
    }

    public function showCashCertification($id): View
    {
        $batch = $this->cashCertificationBatchQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('accounting/cash-certification-batch-show', [
            'workspace' => 'cash_certifier',
            'batch' => $batch,
        ]);
    }

    public function showAccountingOfficerOrs(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showAccountingOfficerDv(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showAccountingOfficerLddapAda(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function showAccountingApproverOrs(Application $application)
    {
        $application = $this->accountingApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showAccountingApproverDv(Application $application)
    {
        $application = $this->accountingApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showAccountingApproverLddapAda(Application $application)
    {
        $application = $this->accountingApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function showCashCertificationOrs(Application $application)
    {
        $application = $this->cashCertificationBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showCashCertificationDv(Application $application)
    {
        $application = $this->cashCertificationBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showCashCertificationLddapAda(Application $application)
    {
        $application = $this->cashCertificationBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function submitAccountingOfficerReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->accountingOfficerBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,for_compliance'],
            'gl_accounting_remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'for_compliance' && blank($validated['gl_accounting_remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'gl_accounting_remarks' => 'Compliance remarks are required when returning the case to budget approval.',
            ]);
        }

        $remarks = filled($validated['gl_accounting_remarks'] ?? null) ? trim((string) $validated['gl_accounting_remarks']) : null;

        $updatePayload = $validated['decision'] === 'approved'
            ? [
                'gl_accounting_review_status' => 'reviewed',
                'gl_accounting_remarks' => $remarks,
                'gl_accounting_reviewed_by' => $request->user()->id,
                'gl_accounting_reviewed_at' => now(),
                'gl_accounting_approval_status' => 'pending_approval',
                'gl_accounting_approval_remarks' => null,
                'gl_accounting_approved_by' => null,
                'gl_accounting_approved_at' => null,
                'gl_payment_status' => 'for_processing_accounting',
                'gl_batch_status' => 'for_processing_accounting',
            ]
            : [
                'gl_accounting_review_status' => 'pending_review',
                'gl_accounting_remarks' => $remarks,
                'gl_accounting_reviewed_by' => null,
                'gl_accounting_reviewed_at' => null,
                'gl_budget_reviewed_by' => null,
                'gl_budget_reviewed_at' => null,
                'gl_budget_approval_status' => null,
                'gl_budget_approval_remarks' => null,
                'gl_budget_approved_by' => null,
                'gl_budget_approved_at' => null,
                'gl_payment_status' => 'for_compliance_budget_officer',
                'gl_batch_status' => 'for_compliance_budget_officer',
            ];

        $now = now();

        DB::transaction(function () use ($application, $validated, $updatePayload, $remarks, $now) {
            $application->update($updatePayload);

            if (! $application->gl_finance_batch_id) {
                return;
            }

            $batch = GlFinanceBatch::query()
                ->with('applications:id,gl_finance_batch_id,gl_accounting_reviewed_at')
                ->find($application->gl_finance_batch_id);

            if (! $batch) {
                return;
            }

            if ($validated['decision'] === 'approved') {
                $allReviewed = $batch->applications->every(fn ($item) => ! is_null($item->gl_accounting_reviewed_at));

                if ($allReviewed) {
                    $batch->update([
                        'status' => 'for_processing_accounting',
                        'current_stage' => 'accounting_approval',
                        'accounting_approval_status' => 'pending_approval',
                        'accounting_approval_remarks' => null,
                        'accounting_approved_by' => null,
                        'accounting_approved_at' => null,
                        'decision_notes' => null,
                    ]);
                }

                return;
            }

            $batch->update([
                'status' => 'for_compliance_budget_officer',
                'current_stage' => 'budget_review',
                'accounting_approval_status' => null,
                'accounting_approval_remarks' => null,
                'accounting_approved_by' => null,
                'accounting_approved_at' => null,
                'compliance_trigger_application_id' => $application->id,
                'decision_notes' => $remarks,
            ]);

            Application::query()
                ->where('gl_finance_batch_id', $batch->id)
                ->update([
                    'gl_payment_status' => 'for_compliance_budget_officer',
                    'gl_batch_status' => 'for_compliance_budget_officer',
                    'gl_accounting_remarks' => $remarks,
                    'gl_accounting_review_status' => 'pending_review',
                    'gl_accounting_reviewed_by' => null,
                    'gl_accounting_reviewed_at' => null,
                    'gl_accounting_approval_status' => null,
                    'gl_accounting_approval_remarks' => null,
                    'gl_accounting_approved_by' => null,
                    'gl_accounting_approved_at' => null,
                    'gl_budget_reviewed_by' => null,
                    'gl_budget_reviewed_at' => null,
                    'gl_budget_approval_status' => null,
                    'gl_budget_approval_remarks' => null,
                    'gl_budget_approved_by' => null,
                    'gl_budget_approved_at' => null,
                    'updated_at' => $now,
                ]);
        }, 3);

        $this->auditLogs->log($request, 'gl_payment.accounting_review_submitted', $application, [
            'decision' => $validated['decision'],
            'accounting_remarks' => $validated['gl_accounting_remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        $redirectRoute = $request->filled('batch_id')
            ? route('accounting-officer.gl-payment-reviews.show', $request->input('batch_id'))
            : route('accounting-officer.gl-payment-reviews');

        return redirect()
            ->to($redirectRoute)
            ->with('success', $validated['decision'] === 'approved'
                ? 'Case submitted to the accounting approver successfully.'
                : 'Case returned to the budget officer for compliance.');
    }

    public function submitAccountingApproverDecision(Request $request, $id): RedirectResponse
    {
        $batch = $this->accountingApprovalBatchQuery(false)
            ->with('applications')
            ->findOrFail($id);

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
            'trigger_application_id' => ['nullable', 'integer'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the accounting review.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Compliance remarks are required when returning the case to the accounting officer.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['trigger_application_id'] ?? null)) {
            throw ValidationException::withMessages([
                'trigger_application_id' => 'Select the specific record that triggered the compliance return.',
            ]);
        }

        $triggerApplicationId = filled($validated['trigger_application_id'] ?? null)
            ? (int) $validated['trigger_application_id']
            : null;

        if ($triggerApplicationId && ! $batch->applications->contains('id', $triggerApplicationId)) {
            throw ValidationException::withMessages([
                'trigger_application_id' => 'The selected compliance trigger record is not part of this batch.',
            ]);
        }

        $remarks = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
        $now = now();

        DB::transaction(function () use ($batch, $validated, $remarks, $triggerApplicationId, $now, $request) {
            $batchPayload = [
                'accounting_approval_status' => $validated['decision'],
                'accounting_approval_remarks' => $remarks,
                'accounting_approved_by' => $request->user()->id,
                'accounting_approved_at' => $now,
                'compliance_trigger_application_id' => $validated['decision'] === 'for_compliance' ? $triggerApplicationId : null,
                'decision_notes' => $remarks,
            ];

            $applicationPayload = [
                'gl_accounting_approval_status' => $validated['decision'],
                'gl_accounting_approval_remarks' => $remarks,
                'gl_accounting_approved_by' => $request->user()->id,
                'gl_accounting_approved_at' => $now,
                'updated_at' => $now,
            ];

            if ($validated['decision'] === 'approved') {
                $batchPayload['status'] = 'for_processing_program_amount_approval';
                $batchPayload['current_stage'] = 'program_amount_approval';
                $applicationPayload['gl_payment_status'] = 'for_processing_program_amount_approval';
                $applicationPayload['gl_batch_status'] = 'for_processing_program_amount_approval';
                $applicationPayload['gl_program_amount_approval_status'] = 'pending_approval';
                $applicationPayload['gl_program_amount_approval_remarks'] = null;
                $applicationPayload['gl_program_amount_approved_by'] = null;
                $applicationPayload['gl_program_amount_approved_at'] = null;
            } elseif ($validated['decision'] === 'for_compliance') {
                $batchPayload['status'] = 'for_compliance_accounting_officer';
                $batchPayload['current_stage'] = 'accounting_review';
                $applicationPayload['gl_payment_status'] = 'for_compliance_accounting_officer';
                $applicationPayload['gl_batch_status'] = 'for_compliance_accounting_officer';
                $applicationPayload['gl_accounting_review_status'] = 'pending_review';
                $applicationPayload['gl_accounting_remarks'] = $remarks;
                $applicationPayload['gl_accounting_reviewed_by'] = null;
                $applicationPayload['gl_accounting_reviewed_at'] = null;
                $applicationPayload['gl_accounting_approval_status'] = null;
                $applicationPayload['gl_accounting_approved_by'] = null;
                $applicationPayload['gl_accounting_approved_at'] = null;
            } else {
                $batchPayload['status'] = 'disapproved';
                $batchPayload['current_stage'] = null;
                $applicationPayload['gl_batch_status'] = 'disapproved';
            }

            $batch->update($batchPayload);

            Application::query()
                ->whereIn('id', $batch->applications->pluck('id'))
                ->update($applicationPayload);
        }, 3);

        $this->auditLogs->log($request, 'gl_finance_batch.accounting_approval_updated', $batch, [
            'decision' => $validated['decision'],
            'remarks' => $remarks,
            'trigger_application_id' => $triggerApplicationId,
            'batch_no' => $batch->batch_no,
        ]);

        return redirect()
            ->route('accounting-approver.gl-payment-approvals')
            ->with('success', 'Accounting batch approval decision saved successfully.');
    }

    public function showAccountingApproverBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->accountingApprovalBatchQuery(true)->findOrFail($batchId);

        $application = $this->baseQuery(true)
            ->whereIn('applications.id', $batch->applications->pluck('id'))
            ->whereKey($applicationId)
            ->firstOrFail();

        return view('accounting.show', array_merge(
            $this->renderShowView($application, 'approver')->getData(),
            [
                'batch' => $batch,
                'readOnlyBatchRecord' => true,
                'readOnlyBatchBackUrl' => route('accounting-approver.gl-payment-approvals.show', $batch->id),
            ]
        ));
    }

    public function submitCashCertificationDecision(Request $request, $id): RedirectResponse
    {
        $batch = $this->cashCertificationBatchQuery(false)
            ->with('applications')
            ->findOrFail($id);

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
            'trigger_application_id' => ['nullable', 'integer'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the cash certification.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Compliance remarks are required when returning the case to the cash officer.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['trigger_application_id'] ?? null)) {
            throw ValidationException::withMessages([
                'trigger_application_id' => 'Select the specific record that triggered the compliance return.',
            ]);
        }

        $triggerApplicationId = filled($validated['trigger_application_id'] ?? null)
            ? (int) $validated['trigger_application_id']
            : null;

        if ($triggerApplicationId && ! $batch->applications->contains('id', $triggerApplicationId)) {
            throw ValidationException::withMessages([
                'trigger_application_id' => 'The selected compliance trigger record is not part of this batch.',
            ]);
        }

        $remarks = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
        $now = now();

        DB::transaction(function () use ($batch, $validated, $remarks, $triggerApplicationId, $now, $request) {
            $batchPayload = [
                'accounting_certification_status' => $validated['decision'],
                'accounting_certification_remarks' => $remarks,
                'accounting_certified_by' => $request->user()->id,
                'accounting_certified_at' => $now,
                'compliance_trigger_application_id' => $validated['decision'] === 'for_compliance' ? $triggerApplicationId : null,
                'decision_notes' => $remarks,
            ];

            $applicationPayload = [
                'gl_cash_certification_status' => $validated['decision'],
                'gl_cash_certification_remarks' => $remarks,
                'gl_cash_certified_by' => $request->user()->id,
                'gl_cash_certified_at' => $now,
                'updated_at' => $now,
            ];

            if ($validated['decision'] === 'approved') {
                $batchPayload['status'] = 'for_processing_finance_director';
                $batchPayload['current_stage'] = 'finance_director';
                $applicationPayload['gl_payment_status'] = 'for_processing_finance_director';
                $applicationPayload['gl_batch_status'] = 'for_processing_finance_director';
                $applicationPayload['gl_finance_director_status'] = 'pending_approval';
                $applicationPayload['gl_finance_director_remarks'] = null;
                $applicationPayload['gl_finance_director_approved_by'] = null;
                $applicationPayload['gl_finance_director_approved_at'] = null;
            } elseif ($validated['decision'] === 'for_compliance') {
                $batchPayload['status'] = 'for_compliance_cash_officer';
                $batchPayload['current_stage'] = 'cash_review';
                $applicationPayload['gl_payment_status'] = 'for_compliance_cash_officer';
                $applicationPayload['gl_batch_status'] = 'for_compliance_cash_officer';
                $applicationPayload['gl_cash_review_status'] = 'pending_review';
                $applicationPayload['gl_cash_remarks'] = $remarks;
                $applicationPayload['gl_cash_reviewed_by'] = null;
                $applicationPayload['gl_cash_reviewed_at'] = null;
                $applicationPayload['gl_cash_approval_status'] = null;
                $applicationPayload['gl_cash_approval_remarks'] = null;
                $applicationPayload['gl_cash_approved_by'] = null;
                $applicationPayload['gl_cash_approved_at'] = null;
                $applicationPayload['gl_cash_certification_status'] = null;
                $applicationPayload['gl_cash_certification_remarks'] = null;
                $applicationPayload['gl_cash_certified_by'] = null;
                $applicationPayload['gl_cash_certified_at'] = null;
            } else {
                $batchPayload['status'] = 'disapproved';
                $batchPayload['current_stage'] = null;
                $applicationPayload['gl_batch_status'] = 'disapproved';
            }

            $batch->update($batchPayload);

            Application::query()
                ->whereIn('id', $batch->applications->pluck('id'))
                ->update($applicationPayload);
        }, 3);

        $this->auditLogs->log($request, 'gl_finance_batch.cash_certification_updated', $batch, [
            'decision' => $validated['decision'],
            'remarks' => $remarks,
            'trigger_application_id' => $triggerApplicationId,
            'batch_no' => $batch->batch_no,
        ]);

        return redirect()
            ->route('accounting-approver.cash-certifications')
            ->with('success', 'Accounting certification batch decision saved successfully.');
    }

    public function showCashCertificationBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->cashCertificationBatchQuery(true)->findOrFail($batchId);

        $application = $this->baseQuery(true)
            ->whereIn('applications.id', $batch->applications->pluck('id'))
            ->whereKey($applicationId)
            ->firstOrFail();

        return view('accounting.show', array_merge(
            $this->renderShowView($application, 'cash_certifier')->getData(),
            [
                'batch' => $batch,
                'readOnlyBatchRecord' => true,
                'readOnlyBatchBackUrl' => route('accounting-approver.cash-certifications.show', $batch->id),
                'readOnlyBatchBackText' => 'Back to Accounting Certification Batch',
            ]
        ));
    }

    protected function buildQueuePayload(Request $request, string $workspace): array
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = match ($workspace) {
            'officer' => $filters['scope'] === 'finished' ? $this->accountingOfficerFinishedQuery() : $this->accountingOfficerBaseQuery(false),
            'cash_certifier' => $filters['scope'] === 'finished' ? $this->cashCertificationFinishedQuery() : $this->cashCertificationBaseQuery(false),
            default => $filters['scope'] === 'finished' ? $this->accountingApproverFinishedQuery() : $this->accountingApproverBaseQuery(false),
        };

        $query = clone $sourceQuery;

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($inner) use ($search) {
                $inner->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    })
                    ->orWhereHas('serviceProvider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['fund_source'] !== '' && $filters['fund_source'] !== 'all') {
            $query->where('gl_finance_fund_source', $filters['fund_source']);
        }

        if ($filters['payment_status'] !== '' && $filters['payment_status'] !== 'all') {
            $query->where('gl_payment_status', $filters['payment_status']);
        }

        $applications = $query
            ->latest(match ($workspace) {
                'officer' => 'gl_program_approved_at',
                'cash_certifier' => 'gl_cash_approved_at',
                default => 'gl_accounting_reviewed_at',
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = (clone $sourceQuery)
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $paymentStatusOptions = (clone $sourceQuery)
            ->whereNotNull('gl_payment_status')
            ->distinct()
            ->orderBy('gl_payment_status')
            ->pluck('gl_payment_status');

        $statsQuery = clone $sourceQuery;

        $queueStats = [
            'total' => (clone $statsQuery)->count(),
            'with_remarks' => (clone $statsQuery)
                ->where(function ($remarkQuery) {
                    $remarkQuery->whereNotNull('gl_budget_remarks')->where('gl_budget_remarks', '!=', '')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', '');
                        })->orWhere(function ($inner) {
                            $inner->whereNotNull('gl_cash_approval_remarks')->where('gl_cash_approval_remarks', '!=', '');
                        })->orWhere(function ($inner) {
                            $inner->whereNotNull('gl_cash_certification_remarks')->where('gl_cash_certification_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return [$applications, $filters, $fundSources, $queueStats, $paymentStatusOptions];
    }

    protected function accountingOfficerBatches(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->accountingOfficerBatchFinishedQuery()
            : $this->accountingOfficerBatchQuery(false);

        $query = clone $sourceQuery;

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($inner) use ($search) {
                $inner->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('serviceProvider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('applications', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('reference_no', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($clientQuery) use ($search) {
                                $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                            });
                    });
            });
        }

        if ($filters['fund_source'] !== '' && $filters['fund_source'] !== 'all') {
            $query->where('finance_fund_source_name', $filters['fund_source']);
        }

        if ($filters['payment_status'] !== '' && $filters['payment_status'] !== 'all') {
            $query->where('status', $filters['payment_status']);
        }

        $batches = $query->latest('updated_at')->paginate(10)->withQueryString();
        $fundSources = (clone $sourceQuery)->whereNotNull('finance_fund_source_name')->distinct()->orderBy('finance_fund_source_name')->pluck('finance_fund_source_name');
        $paymentStatusOptions = (clone $sourceQuery)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
        $queueStats = [
            'total' => (clone $sourceQuery)->count(),
            'with_remarks' => (clone $sourceQuery)
                ->where(function ($remarkQuery) {
                    $remarkQuery->whereNotNull('decision_notes')->where('decision_notes', '!=', '')
                        ->orWhereHas('applications', fn ($applicationQuery) => $applicationQuery->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', ''));
                })
                ->count(),
        ];

        return view('accounting.officer-batch-queue', compact('batches', 'filters', 'fundSources', 'paymentStatusOptions', 'queueStats'));
    }

    protected function accountingOfficerFinishedQuery()
    {
        return $this->accountingOfficerBaseQuery(true)
            ->where('gl_accounting_reviewed_by', auth()->id());
    }

    protected function accountingOfficerBatchFinishedQuery()
    {
        return $this->accountingOfficerBatchQuery(true)
            ->whereHas('applications', fn ($applicationQuery) => $applicationQuery->where('gl_accounting_reviewed_by', auth()->id()));
    }

    protected function accountingApproverFinishedQuery()
    {
        return $this->accountingApproverBaseQuery(true)
            ->where('gl_accounting_approved_by', auth()->id());
    }

    protected function cashCertificationBatches(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->cashCertificationBatchFinishedQuery()
            : $this->cashCertificationBatchQuery(false);

        $query = clone $sourceQuery;

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($inner) use ($search) {
                $inner->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('serviceProvider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('applications', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('reference_no', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($clientQuery) use ($search) {
                                $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                            });
                    });
            });
        }

        if ($filters['fund_source'] !== '' && $filters['fund_source'] !== 'all') {
            $query->where('finance_fund_source_name', $filters['fund_source']);
        }

        if ($filters['payment_status'] !== '' && $filters['payment_status'] !== 'all') {
            $query->where('status', $filters['payment_status']);
        }

        $batches = $query
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = (clone $sourceQuery)
            ->whereNotNull('finance_fund_source_name')
            ->distinct()
            ->orderBy('finance_fund_source_name')
            ->pluck('finance_fund_source_name');

        $paymentStatusOptions = (clone $sourceQuery)
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $queueStats = [
            'total' => (clone $sourceQuery)->count(),
            'with_remarks' => (clone $sourceQuery)
                ->where(function ($remarkQuery) {
                    $remarkQuery->whereNotNull('decision_notes')->where('decision_notes', '!=', '')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('accounting_certification_remarks')->where('accounting_certification_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return view('accounting.cash-certification-batches', [
            'workspace' => 'cash_certifier',
            'batches' => $batches,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => $queueStats,
        ]);
    }

    protected function accountingApprovalBatches(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->accountingApprovalBatchFinishedQuery()
            : $this->accountingApprovalBatchQuery(false);

        $query = clone $sourceQuery;

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($inner) use ($search) {
                $inner->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('serviceProvider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('applications', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('reference_no', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($clientQuery) use ($search) {
                                $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                            });
                    });
            });
        }

        if ($filters['fund_source'] !== '' && $filters['fund_source'] !== 'all') {
            $query->where('finance_fund_source_name', $filters['fund_source']);
        }

        if ($filters['payment_status'] !== '' && $filters['payment_status'] !== 'all') {
            $query->where('status', $filters['payment_status']);
        }

        $batches = $query
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = (clone $sourceQuery)
            ->whereNotNull('finance_fund_source_name')
            ->distinct()
            ->orderBy('finance_fund_source_name')
            ->pluck('finance_fund_source_name');

        $paymentStatusOptions = (clone $sourceQuery)
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $queueStats = [
            'total' => (clone $sourceQuery)->count(),
            'with_remarks' => (clone $sourceQuery)
                ->where(function ($remarkQuery) {
                    $remarkQuery->whereNotNull('decision_notes')->where('decision_notes', '!=', '')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('accounting_approval_remarks')->where('accounting_approval_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return view('accounting.batch-queue', [
            'workspace' => 'approver',
            'batches' => $batches,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => $queueStats,
        ]);
    }

    protected function cashCertificationFinishedQuery()
    {
        return $this->cashCertificationBaseQuery(true)
            ->where('gl_cash_certified_by', auth()->id());
    }

    protected function cashCertificationBatchFinishedQuery()
    {
        return $this->cashCertificationBatchQuery(true)
            ->where('accounting_certified_by', auth()->id());
    }

    protected function renderShowView(Application $application, string $workspace): View
    {
        return view('accounting.show', [
            'workspace' => $workspace,
            'application' => $application,
            'statementDocuments' => $application->documents
                ->where('document_type', 'Updated Statement of Account')
                ->sortByDesc('created_at')
                ->values(),
            'supportingDocuments' => $application->documents
                ->where('document_type', 'Other Supporting Document')
                ->sortByDesc('created_at')
                ->values(),
        ]);
    }

    protected function providerLoadRows($query, int $limit = 5)
    {
        $amountSql = Application::effectiveDisplayedAmountSql();

        return (clone $query)
            ->selectRaw('service_provider_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM('.$amountSql.') as amount')
            ->groupBy('service_provider_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'provider' => $row->serviceProvider?->name ?? 'Unassigned Provider',
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->values();
    }

    protected function batchProviderLoadRows($query, int $limit = 5)
    {
        return (clone $query)
            ->selectRaw('service_provider_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(total_amount) as amount')
            ->groupBy('service_provider_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'provider' => $row->serviceProvider?->name ?? 'Unassigned Provider',
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->values();
    }

    protected function accountingOfficerBaseQuery(bool $includeHandled = false, bool $withDocuments = false)
    {
        return $this->baseQuery($withDocuments)
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereIn('gl_payment_status', ['for_processing_accounting', 'for_compliance_accounting_officer'])
                        ->where(function ($inner) {
                            $inner->where('gl_accounting_review_status', 'pending_review')
                                ->orWhereNull('gl_accounting_review_status');
                        });
                });

                if ($includeHandled) {
                    $query->orWhere('gl_accounting_reviewed_by', auth()->id());
                }
            });
    }

    protected function accountingOfficerBatchQuery(bool $includeHandled = false)
    {
        return GlFinanceBatch::query()
            ->with([
                'serviceProvider',
                'bankAccount.bank',
                'applications.client',
                'applications.serviceProvider',
                'applications.assistanceType',
                'applications.assistanceDetail',
            ])
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($activeQuery) {
                    $activeQuery->where('current_stage', 'accounting_review')
                        ->whereIn('status', ['for_processing_accounting', 'for_compliance_accounting_officer']);
                });

                if ($includeHandled) {
                    $query->orWhereHas('applications', fn ($applicationQuery) => $applicationQuery->where('gl_accounting_reviewed_by', auth()->id()));
                }
            });
    }

    protected function accountingApproverBaseQuery(bool $includeHandled = false, bool $withDocuments = false)
    {
        return $this->baseQuery($withDocuments)
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->where('gl_payment_status', 'for_processing_accounting')
                        ->where('gl_accounting_review_status', 'reviewed')
                        ->where('gl_accounting_approval_status', 'pending_approval');
                });

                if ($includeHandled) {
                    $query->orWhere('gl_accounting_approved_by', auth()->id());
                }
            });
    }

    protected function cashCertificationBaseQuery(bool $includeHandled = false, bool $withDocuments = false)
    {
        return $this->baseQuery($withDocuments)
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->where('gl_payment_status', 'for_processing_accounting_certification')
                        ->where('gl_cash_approval_status', 'approved')
                        ->where(function ($inner) {
                            $inner->where('gl_cash_certification_status', 'pending_approval')
                                ->orWhereNull('gl_cash_certification_status');
                        });
                });

                if ($includeHandled) {
                    $query->orWhere('gl_cash_certified_by', auth()->id());
                }
            });
    }

    protected function cashCertificationBatchQuery(bool $includeHandled = false)
    {
        return GlFinanceBatch::query()
            ->with([
                'serviceProvider',
                'bankAccount.bank',
                'applications.client',
                'applications.serviceProvider',
                'applications.assistanceType',
                'applications.assistanceDetail',
            ])
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($activeQuery) {
                    $activeQuery->where('current_stage', 'accounting_certification')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('accounting_certification_status')
                                ->orWhere('accounting_certification_status', 'pending_approval')
                                ->orWhere('accounting_certification_status', 'for_compliance');
                        })
                        ->whereIn('status', ['for_processing_accounting_certification', 'for_compliance_cash_officer']);
                });

                if ($includeHandled) {
                    $query->orWhere('accounting_certified_by', auth()->id());
                }
            });
    }

    protected function accountingApprovalBatchQuery(bool $includeHandled = false)
    {
        return GlFinanceBatch::query()
            ->with([
                'serviceProvider',
                'bankAccount.bank',
                'applications.client',
                'applications.serviceProvider',
                'applications.assistanceType',
                'applications.assistanceDetail',
            ])
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($activeQuery) {
                    $activeQuery->where('current_stage', 'accounting_approval')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('accounting_approval_status')
                                ->orWhere('accounting_approval_status', 'pending_approval')
                                ->orWhere('accounting_approval_status', 'for_compliance');
                        })
                        ->whereIn('status', ['for_processing_accounting', 'for_compliance_accounting_officer']);
                });

                if ($includeHandled) {
                    $query->orWhere('accounting_approved_by', auth()->id());
                }
            });
    }

    protected function accountingApprovalBatchFinishedQuery()
    {
        return $this->accountingApprovalBatchQuery(true)
            ->where('accounting_approved_by', auth()->id());
    }

    protected function baseQuery(bool $withDocuments = false)
    {
        $relations = [
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'assistanceRecommendations.assistanceType',
                'assistanceRecommendations.assistanceSubtype',
                'assistanceRecommendations.assistanceDetail',
                'assistanceRecommendations.modeOfAssistance',
                'serviceProvider',
                'modeOfAssistance',
                'glBudgetReviewer',
                'glProgramApprover',
                'glAccountingReviewer',
                'glAccountingApprover',
                'glCashReviewer',
                'glCashApprover',
                'glCashCertifier',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'));
    }
}
