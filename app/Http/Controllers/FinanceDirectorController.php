<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
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
        $baseQuery = $this->baseQuery(true);
        $amountSql = Application::effectiveDisplayedAmountSql();

        return view('finance-director.dashboard', [
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'with_remarks' => (clone $baseQuery)->whereNotNull('gl_cash_certification_remarks')->where('gl_cash_certification_remarks', '!=', '')->count(),
                'with_supporting_docs' => (clone $baseQuery)->whereHas('documents', fn ($query) => $query->where('document_type', 'Other Supporting Document'))->count(),
                'total_amount' => (float) ((clone $baseQuery)->sum(DB::raw($amountSql))),
            ],
            'recentCases' => (clone $baseQuery)->latest('gl_cash_certified_at')->latest('updated_at')->take(6)->get(),
            'providerLoad' => (clone $baseQuery)
                ->selectRaw('service_provider_id')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM('.$amountSql.') as amount')
                ->groupBy('service_provider_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'provider' => $row->serviceProvider?->name ?? 'Unassigned Provider',
                    'total' => (int) $row->total,
                    'amount' => (float) $row->amount,
                ])
                ->values(),
        ]);
    }

    public function queue(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->finishedQuery()
            : $this->baseQuery(false);

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
            ->latest('gl_cash_certified_at')
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

        return view('finance-director.queue', [
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => [
                'total' => (clone $statsQuery)->count(),
                'with_remarks' => (clone $statsQuery)
                    ->where(function ($remarkQuery) {
                        $remarkQuery->whereNotNull('gl_cash_approval_remarks')->where('gl_cash_approval_remarks', '!=', '')
                            ->orWhere(function ($inner) {
                                $inner->whereNotNull('gl_cash_certification_remarks')->where('gl_cash_certification_remarks', '!=', '');
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

    public function show(Application $application): View
    {
        $application = $this->baseQuery(true, true)
            ->whereKey($application->id)
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

    public function update(Request $request, Application $application): RedirectResponse
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
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

        $updatePayload = [
            'gl_finance_director_status' => $validated['decision'],
            'gl_finance_director_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_finance_director_approved_by' => $request->user()->id,
            'gl_finance_director_approved_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            $updatePayload['gl_payment_status'] = 'paid';
        } elseif ($validated['decision'] === 'for_compliance') {
            $updatePayload['gl_payment_status'] = 'for_compliance_cash_officer';
            $updatePayload['gl_cash_review_status'] = 'pending_review';
            $updatePayload['gl_cash_remarks'] = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
            $updatePayload['gl_cash_reviewed_by'] = null;
            $updatePayload['gl_cash_reviewed_at'] = null;
            $updatePayload['gl_cash_approval_status'] = null;
            $updatePayload['gl_cash_approval_remarks'] = null;
            $updatePayload['gl_cash_approved_by'] = null;
            $updatePayload['gl_cash_approved_at'] = null;
            $updatePayload['gl_cash_certification_status'] = null;
            $updatePayload['gl_cash_certification_remarks'] = null;
            $updatePayload['gl_cash_certified_by'] = null;
            $updatePayload['gl_cash_certified_at'] = null;
            $updatePayload['gl_finance_director_status'] = null;
            $updatePayload['gl_finance_director_remarks'] = null;
            $updatePayload['gl_finance_director_approved_by'] = null;
            $updatePayload['gl_finance_director_approved_at'] = null;
        } else {
            $updatePayload['gl_payment_status'] = 'for_processing_finance_director';
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.finance_director_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        return redirect()
            ->route('finance-director.gl-payment-approvals')
            ->with('success', $validated['decision'] === 'approved'
                ? 'Final approval saved and the case is now tagged as Paid.'
                : ($validated['decision'] === 'for_compliance'
                    ? 'Case returned to the cash officer for compliance.'
                    : 'Finance director decision saved successfully.'));
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
