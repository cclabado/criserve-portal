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

class FinanceDirectorController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function dashboard(): View
    {
        $baseQuery = $this->batchBaseQuery(true);

        return view('finance-director.dashboard', [
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_remarks' => (clone $baseQuery)
                    ->where(function ($remarkQuery) {
                        $remarkQuery->whereNotNull('decision_notes')->where('decision_notes', '!=', '')
                            ->orWhere(function ($inner) {
                                $inner->whereNotNull('finance_director_remarks')->where('finance_director_remarks', '!=', '');
                            });
                    })
                    ->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('applications.documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum('total_amount')),
            ],
            'recentBatches' => (clone $baseQuery)->latest('updated_at')->take(6)->get(),
            'providerLoad' => $this->batchProviderLoadRows(clone $baseQuery),
        ]);
    }

    public function queue(Request $request): View
    {
        return $this->batchQueue($request);
    }

    protected function batchQueue(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->finishedBatchQuery()
            : $this->batchBaseQuery(false);

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

        $statsQuery = clone $sourceQuery;

        return view('finance-director.batch-queue', [
            'batches' => $batches,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => [
                'total' => (clone $statsQuery)->count(),
                'with_remarks' => (clone $statsQuery)
                    ->where(function ($remarkQuery) {
                        $remarkQuery->whereNotNull('decision_notes')->where('decision_notes', '!=', '')
                            ->orWhere(function ($inner) {
                                $inner->whereNotNull('finance_director_remarks')->where('finance_director_remarks', '!=', '');
                            });
                    })
                    ->count(),
            ],
        ]);
    }

    protected function finishedQuery()
    {
        return $this->baseQuery(true)
            ->where('gl_finance_director_approved_by', auth()->id());
    }

    protected function batchProviderLoadRows($query, int $limit = 5)
    {
        return $query
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

    protected function finishedBatchQuery()
    {
        return $this->batchBaseQuery(true)
            ->where('finance_director_approved_by', auth()->id());
    }

    public function show($id): View
    {
        $batch = $this->batchBaseQuery(true)
            ->with('applications')
            ->findOrFail($id);

        return view('finance-director.batch-show', [
            'batch' => $batch,
        ]);
    }

    public function showBatchRecord($batchId, $applicationId): View
    {
        $batch = $this->batchBaseQuery(true)->findOrFail($batchId);

        $application = $this->baseQuery(true, true)
            ->whereIn('applications.id', $batch->applications->pluck('id'))
            ->whereKey($applicationId)
            ->firstOrFail();

        return view('finance-director.show', [
            'application' => $application,
            'statementDocuments' => $application->documents
                ->where('document_type', 'Updated Statement of Account')
                ->sortByDesc('created_at')
                ->values(),
            'supportingDocuments' => $application->documents
                ->where('document_type', 'Other Supporting Document')
                ->sortByDesc('created_at')
                ->values(),
            'readOnlyBatchRecord' => true,
            'batch' => $batch,
            'readOnlyBatchBackUrl' => route('finance-director.gl-payment-approvals.show', $batch->id),
        ]);
    }

    public function showOrs(Application $application)
    {
        $application = $this->baseQuery(true, true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showDv(Application $application)
    {
        $application = $this->baseQuery(true, true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showLddapAda(Application $application)
    {
        $application = $this->baseQuery(true, true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $batch = $this->batchBaseQuery(false)
            ->with('applications')
            ->findOrFail($id);

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
            'trigger_application_id' => ['nullable', 'integer'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the finance director review.',
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
                'finance_director_status' => $validated['decision'],
                'finance_director_remarks' => $remarks,
                'finance_director_approved_by' => $request->user()->id,
                'finance_director_approved_at' => $now,
                'compliance_trigger_application_id' => $validated['decision'] === 'for_compliance' ? $triggerApplicationId : null,
                'decision_notes' => $remarks,
            ];

            $applicationPayload = [
                'gl_finance_director_status' => $validated['decision'],
                'gl_finance_director_remarks' => $remarks,
                'gl_finance_director_approved_by' => $request->user()->id,
                'gl_finance_director_approved_at' => $now,
                'updated_at' => $now,
            ];

            if ($validated['decision'] === 'approved') {
                $batchPayload['status'] = 'paid';
                $batchPayload['current_stage'] = 'completed';
                $batchPayload['completed_at'] = $now;
                $applicationPayload['gl_payment_status'] = 'paid';
                $applicationPayload['gl_batch_status'] = 'paid';
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
                $applicationPayload['gl_finance_director_status'] = null;
                $applicationPayload['gl_finance_director_remarks'] = null;
                $applicationPayload['gl_finance_director_approved_by'] = null;
                $applicationPayload['gl_finance_director_approved_at'] = null;
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

        $this->auditLogs->log($request, 'gl_finance_batch.finance_director_updated', $batch, [
            'decision' => $validated['decision'],
            'remarks' => $remarks,
            'trigger_application_id' => $triggerApplicationId,
            'batch_no' => $batch->batch_no,
        ]);

        return redirect()
            ->route('finance-director.gl-payment-approvals')
            ->with('success', $validated['decision'] === 'approved'
                ? 'Final batch approval saved and the cases are now tagged as Paid.'
                : ($validated['decision'] === 'for_compliance'
                    ? 'Batch returned to the cash officer for compliance.'
                    : 'Finance director batch decision saved successfully.'));
    }

    protected function batchBaseQuery(bool $includeHandled = false)
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
            ->where(function ($rootQuery) use ($includeHandled) {
                $rootQuery->where(function ($statusQuery) {
                    $statusQuery->where('status', 'for_processing_finance_director')
                        ->where('current_stage', 'finance_director')
                        ->where(function ($inner) {
                            $inner->where('finance_director_status', 'pending_approval')
                                ->orWhereNull('finance_director_status')
                                ->orWhere('finance_director_status', 'for_compliance');
                        });
                });

                if ($includeHandled) {
                    $rootQuery->orWhere('finance_director_approved_by', auth()->id());
                }
            });
    }

    protected function baseQuery(bool $includeHandled = false, bool $withDocuments = false)
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
                'glCashApprover',
                'glCashCertifier',
                'glFinanceDirectorApprover',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($rootQuery) use ($includeHandled) {
                $rootQuery->where(function ($statusQuery) {
                    $statusQuery->where('gl_payment_status', 'for_processing_finance_director')
                        ->where(function ($inner) {
                            $inner->where('gl_finance_director_status', 'pending_approval')
                                ->orWhereNull('gl_finance_director_status');
                        });
                });

                if ($includeHandled) {
                    $rootQuery->orWhere('gl_finance_director_approved_by', auth()->id());
                }
            });
    }
}
