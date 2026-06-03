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

class CashController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function cashOfficerDashboard(): View
    {
        $baseQuery = $this->cashOfficerBatchQuery(true);

        return view('cash.dashboard', [
            'workspace' => 'officer',
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_previous_remarks' => (clone $baseQuery)->whereHas('applications', fn ($query) => $query->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', ''))->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('applications.documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum('total_amount')),
            ],
            'recentCases' => (clone $baseQuery)->latest('updated_at')->take(6)->get(),
            'providerLoad' => $this->batchProviderLoadRows($baseQuery, 5),
        ]);
    }

    public function cashApproverDashboard(): View
    {
        $baseQuery = $this->cashApproverBaseQuery(true);
        $amountSql = Application::effectiveDisplayedAmountSql();

        return view('cash.dashboard', [
            'workspace' => 'approver',
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_previous_remarks' => (clone $baseQuery)->whereNotNull('gl_cash_remarks')->where('gl_cash_remarks', '!=', '')->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum(DB::raw($amountSql))),
            ],
            'recentCases' => (clone $baseQuery)->latest('gl_cash_reviewed_at')->latest('updated_at')->take(6)->get(),
            'providerLoad' => $this->providerLoadRows($baseQuery, 5),
        ]);
    }

    public function cashOfficerQueue(Request $request): View
    {
        return $this->cashOfficerBatches($request);
    }

    public function cashApproverQueue(Request $request): View
    {
        return $this->cashApprovalBatches($request);
    }

    public function showCashOfficer($id): View
    {
        $batch = $this->cashOfficerBatchQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('cash.officer-batch-show', [
            'batch' => $batch,
        ]);
    }

    public function showCashOfficerBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->cashOfficerBatchQuery(true)->findOrFail($batchId);
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

        return view('cash.show', [
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
            'batchBackUrl' => route('cash-officer.gl-payment-reviews.show', $batch->id),
            'batchBackText' => 'Back to Cash Review Batch',
        ]);
    }

    public function showCashApprover($id): View
    {
        $batch = $this->cashApprovalBatchQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('cash.batch-show', [
            'workspace' => 'approver',
            'batch' => $batch,
        ]);
    }

    public function showCashOfficerOrs(Application $application)
    {
        $application = $this->cashOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showCashOfficerDv(Application $application)
    {
        $application = $this->cashOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showCashOfficerLddapAda(Application $application)
    {
        $application = $this->cashOfficerBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function showCashApproverOrs(Application $application)
    {
        $application = $this->cashApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showCashApproverDv(Application $application)
    {
        $application = $this->cashApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showCashApproverLddapAda(Application $application)
    {
        $application = $this->cashApproverBaseQuery(true, true)->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function submitCashOfficerReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->cashOfficerBaseQuery(false, true)
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,for_compliance'],
            'gl_cash_remarks' => ['nullable', 'string', 'max:1500'],
            'gl_nca_number' => ['nullable', 'string', 'max:255'],
            'gl_nca_date' => ['nullable', 'date'],
            'gl_servicing_bank_branch' => ['nullable', 'string', 'max:255'],
            'gl_mds_sub_account_number' => ['nullable', 'string', 'max:255'],
            'gl_withholding_tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validated['decision'] === 'for_compliance' && blank($validated['gl_cash_remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'gl_cash_remarks' => 'Compliance remarks are required when returning the case to the accounting officer.',
            ]);
        }

        $remarks = filled($validated['gl_cash_remarks'] ?? null) ? trim((string) $validated['gl_cash_remarks']) : null;
        $latestStatement = $application->documents
            ->where('document_type', 'Updated Statement of Account')
            ->sortByDesc('created_at')
            ->first();

        if ($validated['decision'] === 'approved') {
            $missingFields = [];

            if (! $latestStatement || blank($latestStatement->bank_name_snapshot) || blank($latestStatement->account_number_snapshot)) {
                $missingFields['gl_servicing_bank_branch'] = 'A submitted SOA with a linked service provider bank account is required before the LDDAP-ADA can be generated.';
            }

            if (blank($validated['gl_nca_number'] ?? null)) {
                $missingFields['gl_nca_number'] = 'NCA number is required before the LDDAP-ADA can be generated.';
            }

            if (blank($validated['gl_nca_date'] ?? null)) {
                $missingFields['gl_nca_date'] = 'NCA date is required before the LDDAP-ADA can be generated.';
            }

            if (blank($validated['gl_servicing_bank_branch'] ?? null)) {
                $missingFields['gl_servicing_bank_branch'] = 'Servicing bank branch is required before the LDDAP-ADA can be generated.';
            }

            if (blank($validated['gl_mds_sub_account_number'] ?? null)) {
                $missingFields['gl_mds_sub_account_number'] = 'MDS sub-account number is required before the LDDAP-ADA can be generated.';
            }

            if ($missingFields !== []) {
                throw ValidationException::withMessages($missingFields);
            }
        }

        $lddapAdaNumber = $application->gl_lddap_ada_number ?: $this->generateLddapAdaNumber($application);

        $updatePayload = $validated['decision'] === 'approved'
            ? [
                'gl_cash_review_status' => 'reviewed',
                'gl_cash_remarks' => $remarks,
                'gl_cash_reviewed_by' => $request->user()->id,
                'gl_cash_reviewed_at' => now(),
                'gl_cash_approval_status' => 'pending_approval',
                'gl_cash_approval_remarks' => null,
                'gl_cash_approved_by' => null,
                'gl_cash_approved_at' => null,
                'gl_lddap_ada_number' => $lddapAdaNumber,
                'gl_lddap_ada_date' => $application->gl_lddap_ada_date ?: now()->toDateString(),
                'gl_nca_number' => trim((string) $validated['gl_nca_number']),
                'gl_nca_date' => $validated['gl_nca_date'],
                'gl_servicing_bank_branch' => trim((string) $validated['gl_servicing_bank_branch']),
                'gl_mds_sub_account_number' => trim((string) $validated['gl_mds_sub_account_number']),
                'gl_withholding_tax_amount' => round((float) ($validated['gl_withholding_tax_amount'] ?? 0), 2),
                'gl_payment_status' => 'for_processing_cash',
            ]
            : [
                'gl_cash_review_status' => 'pending_review',
                'gl_cash_remarks' => $remarks,
                'gl_cash_reviewed_by' => null,
                'gl_cash_reviewed_at' => null,
                'gl_accounting_review_status' => 'pending_review',
                'gl_accounting_remarks' => $remarks,
                'gl_accounting_reviewed_by' => null,
                'gl_accounting_reviewed_at' => null,
                'gl_accounting_approval_status' => null,
                'gl_accounting_approval_remarks' => null,
                'gl_accounting_approved_by' => null,
                'gl_accounting_approved_at' => null,
                'gl_program_amount_approval_status' => null,
                'gl_program_amount_approval_remarks' => null,
                'gl_program_amount_approved_by' => null,
                'gl_program_amount_approved_at' => null,
                'gl_payment_status' => 'for_compliance_accounting_officer',
            ];

        $now = now();

        DB::transaction(function () use ($application, $updatePayload, $validated, $remarks, $now) {
            $application->update($updatePayload);

            if (! $application->gl_finance_batch_id) {
                return;
            }

            $batch = GlFinanceBatch::query()
                ->with('applications:id,gl_finance_batch_id,gl_cash_reviewed_at')
                ->find($application->gl_finance_batch_id);

            if (! $batch) {
                return;
            }

            if ($validated['decision'] === 'approved') {
                $allReviewed = $batch->applications->every(fn ($item) => ! is_null($item->gl_cash_reviewed_at));

                if ($allReviewed) {
                    $batch->update([
                        'status' => 'for_processing_cash',
                        'current_stage' => 'cash_approval',
                        'cash_approval_status' => 'pending_approval',
                        'cash_approval_remarks' => null,
                        'cash_approved_by' => null,
                        'cash_approved_at' => null,
                        'decision_notes' => null,
                    ]);
                }

                return;
            }

            $batch->update([
                'status' => 'for_compliance_accounting_officer',
                'current_stage' => 'accounting_review',
                'cash_approval_status' => null,
                'cash_approval_remarks' => null,
                'cash_approved_by' => null,
                'cash_approved_at' => null,
                'compliance_trigger_application_id' => $application->id,
                'decision_notes' => $remarks,
            ]);

            Application::query()
                ->where('gl_finance_batch_id', $batch->id)
                ->update([
                    'gl_payment_status' => 'for_compliance_accounting_officer',
                    'gl_batch_status' => 'for_compliance_accounting_officer',
                    'gl_cash_remarks' => $remarks,
                    'gl_cash_review_status' => 'pending_review',
                    'gl_cash_reviewed_by' => null,
                    'gl_cash_reviewed_at' => null,
                    'gl_cash_approval_status' => null,
                    'gl_cash_approval_remarks' => null,
                    'gl_cash_approved_by' => null,
                    'gl_cash_approved_at' => null,
                    'gl_accounting_review_status' => 'pending_review',
                    'gl_accounting_remarks' => $remarks,
                    'gl_accounting_reviewed_by' => null,
                    'gl_accounting_reviewed_at' => null,
                    'gl_accounting_approval_status' => null,
                    'gl_accounting_approval_remarks' => null,
                    'gl_accounting_approved_by' => null,
                    'gl_accounting_approved_at' => null,
                    'gl_program_amount_approval_status' => null,
                    'gl_program_amount_approval_remarks' => null,
                    'gl_program_amount_approved_by' => null,
                    'gl_program_amount_approved_at' => null,
                    'updated_at' => $now,
                ]);
        }, 3);

        $this->auditLogs->log($request, 'gl_payment.cash_review_submitted', $application, [
            'decision' => $validated['decision'],
            'cash_remarks' => $validated['gl_cash_remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
            'lddap_ada_number' => $updatePayload['gl_lddap_ada_number'] ?? null,
        ]);

        $redirectRoute = $request->filled('batch_id')
            ? route('cash-officer.gl-payment-reviews.show', $request->input('batch_id'))
            : route('cash-officer.gl-payment-reviews');

        return redirect()
            ->to($redirectRoute)
            ->with('success', $validated['decision'] === 'approved'
                ? 'Case submitted to the cash approver successfully.'
                : 'Case returned to the accounting officer for compliance.');
    }

    public function submitCashApproverDecision(Request $request, $id): RedirectResponse
    {
        $batch = $this->cashApprovalBatchQuery(false)
            ->with('applications')
            ->findOrFail($id);

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
            'trigger_application_id' => ['nullable', 'integer'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the cash review.',
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
                'cash_approval_status' => $validated['decision'],
                'cash_approval_remarks' => $remarks,
                'cash_approved_by' => $request->user()->id,
                'cash_approved_at' => $now,
                'compliance_trigger_application_id' => $validated['decision'] === 'for_compliance' ? $triggerApplicationId : null,
                'decision_notes' => $remarks,
            ];

            $applicationPayload = [
                'gl_cash_approval_status' => $validated['decision'],
                'gl_cash_approval_remarks' => $remarks,
                'gl_cash_approved_by' => $request->user()->id,
                'gl_cash_approved_at' => $now,
                'updated_at' => $now,
            ];

            if ($validated['decision'] === 'approved') {
                $batchPayload['status'] = 'for_processing_accounting_certification';
                $batchPayload['current_stage'] = 'accounting_certification';
                $applicationPayload['gl_cash_certification_status'] = 'pending_approval';
                $applicationPayload['gl_cash_certification_remarks'] = null;
                $applicationPayload['gl_cash_certified_by'] = null;
                $applicationPayload['gl_cash_certified_at'] = null;
                $applicationPayload['gl_payment_status'] = 'for_processing_accounting_certification';
                $applicationPayload['gl_batch_status'] = 'for_processing_accounting_certification';
            } elseif ($validated['decision'] === 'for_compliance') {
                $batchPayload['status'] = 'for_compliance_cash_officer';
                $batchPayload['current_stage'] = 'cash_review';
                $applicationPayload['gl_cash_review_status'] = 'pending_review';
                $applicationPayload['gl_cash_remarks'] = $remarks;
                $applicationPayload['gl_cash_reviewed_by'] = null;
                $applicationPayload['gl_cash_reviewed_at'] = null;
                $applicationPayload['gl_cash_approval_status'] = null;
                $applicationPayload['gl_cash_approved_by'] = null;
                $applicationPayload['gl_cash_approved_at'] = null;
                $applicationPayload['gl_payment_status'] = 'for_compliance_cash_officer';
                $applicationPayload['gl_batch_status'] = 'for_compliance_cash_officer';
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

        $this->auditLogs->log($request, 'gl_finance_batch.cash_approval_updated', $batch, [
            'decision' => $validated['decision'],
            'remarks' => $remarks,
            'trigger_application_id' => $triggerApplicationId,
            'batch_no' => $batch->batch_no,
        ]);

        return redirect()
            ->route('cash-approver.gl-payment-approvals')
            ->with('success', 'Cash batch approval decision saved successfully.');
    }

    public function showCashApproverBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->cashApprovalBatchQuery(true)->findOrFail($batchId);

        $application = $this->baseQuery(true)
            ->whereIn('applications.id', $batch->applications->pluck('id'))
            ->whereKey($applicationId)
            ->firstOrFail();

        return view('cash.show', array_merge(
            $this->renderShowView($application, 'approver')->getData(),
            [
                'batch' => $batch,
                'readOnlyBatchRecord' => true,
                'readOnlyBatchBackUrl' => route('cash-approver.gl-payment-approvals.show', $batch->id),
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

        $sourceQuery = $workspace === 'officer'
            ? ($filters['scope'] === 'finished' ? $this->cashOfficerFinishedQuery() : $this->cashOfficerBaseQuery(false))
            : ($filters['scope'] === 'finished' ? $this->cashApproverFinishedQuery() : $this->cashApproverBaseQuery(false));

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
            ->latest($workspace === 'officer' ? 'gl_program_amount_approved_at' : 'gl_cash_reviewed_at')
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
                    $remarkQuery->whereNotNull('gl_cash_remarks')->where('gl_cash_remarks', '!=', '')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return [$applications, $filters, $fundSources, $queueStats, $paymentStatusOptions];
    }

    protected function cashOfficerBatches(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->cashOfficerBatchFinishedQuery()
            : $this->cashOfficerBatchQuery(false);

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
                        ->orWhereHas('applications', fn ($applicationQuery) => $applicationQuery->whereNotNull('gl_cash_remarks')->where('gl_cash_remarks', '!=', ''));
                })
                ->count(),
        ];

        return view('cash.officer-batch-queue', compact('batches', 'filters', 'fundSources', 'paymentStatusOptions', 'queueStats'));
    }

    protected function cashOfficerFinishedQuery()
    {
        return $this->cashOfficerBaseQuery(true)
            ->where('gl_cash_reviewed_by', auth()->id());
    }

    protected function cashApproverFinishedQuery()
    {
        return $this->cashApproverBaseQuery(true)
            ->where('gl_cash_approved_by', auth()->id());
    }

    protected function cashApprovalBatches(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->cashApprovalBatchFinishedQuery()
            : $this->cashApprovalBatchQuery(false);

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
                            $inner->whereNotNull('cash_approval_remarks')->where('cash_approval_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return view('cash.batch-queue', [
            'workspace' => 'approver',
            'batches' => $batches,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => $queueStats,
        ]);
    }

    protected function renderShowView(Application $application, string $workspace): View
    {
        return view('cash.show', [
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

    protected function cashOfficerBaseQuery(bool $includeHandled = false, bool $withDocuments = false)
    {
        return $this->baseQuery($withDocuments)
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereIn('gl_payment_status', ['for_processing_cash', 'for_compliance_cash_officer'])
                        ->where(function ($inner) {
                            $inner->where('gl_cash_review_status', 'pending_review')
                                ->orWhereNull('gl_cash_review_status');
                        });
                });

                if ($includeHandled) {
                    $query->orWhere('gl_cash_reviewed_by', auth()->id());
                }
            });
    }

    protected function cashOfficerBatchQuery(bool $includeHandled = false)
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
                    $activeQuery->where('current_stage', 'cash_review')
                        ->whereIn('status', ['for_processing_cash', 'for_compliance_cash_officer']);
                });

                if ($includeHandled) {
                    $query->orWhereHas('applications', fn ($applicationQuery) => $applicationQuery->where('gl_cash_reviewed_by', auth()->id()));
                }
            });
    }

    protected function cashOfficerBatchFinishedQuery()
    {
        return $this->cashOfficerBatchQuery(true)
            ->whereHas('applications', fn ($applicationQuery) => $applicationQuery->where('gl_cash_reviewed_by', auth()->id()));
    }

    protected function cashApproverBaseQuery(bool $includeHandled = false, bool $withDocuments = false)
    {
        return $this->baseQuery($withDocuments)
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->where('gl_payment_status', 'for_processing_cash')
                        ->where('gl_cash_review_status', 'reviewed')
                        ->where('gl_cash_approval_status', 'pending_approval');
                });

                if ($includeHandled) {
                    $query->orWhere('gl_cash_approved_by', auth()->id());
                }
            });
    }

    protected function cashApprovalBatchQuery(bool $includeHandled = false)
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
                    $activeQuery->where('current_stage', 'cash_approval')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('cash_approval_status')
                                ->orWhere('cash_approval_status', 'pending_approval')
                                ->orWhere('cash_approval_status', 'for_compliance');
                        })
                        ->whereIn('status', ['for_processing_cash', 'for_compliance_cash_officer']);
                });

                if ($includeHandled) {
                    $query->orWhere('cash_approved_by', auth()->id());
                }
            });
    }

    protected function cashApprovalBatchFinishedQuery()
    {
        return $this->cashApprovalBatchQuery(true)
            ->where('cash_approved_by', auth()->id());
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
                'glProgramAmountApprover',
                'glAccountingReviewer',
                'glAccountingApprover',
                'glCashReviewer',
                'glCashApprover',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'));
    }

    protected function generateLddapAdaNumber(Application $application): string
    {
        return sprintf(
            '01101101-%s-%04d-%s',
            now()->format('m'),
            (int) $application->id,
            now()->format('Y')
        );
    }
}
