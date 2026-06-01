<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $applications = $this->baseQuery()
            ->latest('gl_cash_certified_at')
            ->latest('updated_at')
            ->get();

        return view('finance-director.dashboard', [
            'stats' => [
                'total' => $applications->count(),
                'with_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_cash_certification_remarks))->count(),
                'with_supporting_docs' => $applications->filter(fn (Application $application) => $application->documents->contains(fn ($document) => $document->document_type === 'Other Supporting Document'))->count(),
                'total_amount' => $applications->sum(fn (Application $application) => $application->effectiveDisplayedAmount()),
            ],
            'recentCases' => $applications->take(6)->values(),
            'providerLoad' => $applications
                ->groupBy(fn (Application $application) => $application->serviceProvider?->name ?? 'Unassigned Provider')
                ->map(fn ($group, $providerName) => [
                    'provider' => $providerName,
                    'total' => $group->count(),
                    'amount' => $group->sum(fn (Application $application) => $application->effectiveDisplayedAmount()),
                ])
                ->sortByDesc('total')
                ->take(5)
                ->values(),
        ]);
    }

    public function queue(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
        ];

        $query = $this->baseQuery();

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

        $applications = $query
            ->latest('gl_cash_certified_at')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = $this->baseQuery()
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $statsQuery = $this->baseQuery();

        return view('finance-director.queue', [
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
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

    public function show(Application $application): View
    {
        $application = $this->baseQuery()
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
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showDv(Application $application)
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showLddapAda(Application $application)
    {
        $application = $this->baseQuery()
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

    protected function baseQuery()
    {
        return Application::with([
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
                'glCashApprover',
                'glCashCertifier',
                'glFinanceDirectorApprover',
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_finance_director')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_finance_director_status', 'pending_approval')
                    ->orWhereNull('gl_finance_director_status');
            });
    }
}
