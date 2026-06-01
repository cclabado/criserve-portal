<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $applications = $this->accountingOfficerBaseQuery()
            ->latest('gl_program_approved_at')
            ->latest('updated_at')
            ->get();

        return view('accounting.dashboard', [
            'workspace' => 'officer',
            'stats' => [
                'total' => $applications->count(),
                'with_processor_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_budget_remarks))->count(),
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

    public function accountingApproverDashboard(): View
    {
        $applications = $this->accountingApproverBaseQuery()
            ->latest('gl_accounting_reviewed_at')
            ->latest('updated_at')
            ->get();
        $cashCertificationApplications = $this->cashCertificationBaseQuery()
            ->latest('gl_cash_approved_at')
            ->latest('updated_at')
            ->get();

        return view('accounting.dashboard', [
            'workspace' => 'approver',
            'stats' => [
                'total' => $applications->count(),
                'with_accounting_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_accounting_remarks))->count(),
                'with_supporting_docs' => $applications->filter(fn (Application $application) => $application->documents->contains(fn ($document) => $document->document_type === 'Other Supporting Document'))->count(),
                'total_amount' => $applications->sum(fn (Application $application) => $application->effectiveDisplayedAmount()),
                'cash_certifications_total' => $cashCertificationApplications->count(),
            ],
            'recentCases' => $applications->take(6)->values(),
            'cashCertificationCases' => $cashCertificationApplications->take(6)->values(),
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

    public function accountingOfficerQueue(Request $request): View
    {
        [$applications, $filters, $fundSources, $queueStats] = $this->buildQueuePayload($request, 'officer');

        return view('accounting.queue', [
            'workspace' => 'officer',
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function accountingApproverQueue(Request $request): View
    {
        [$applications, $filters, $fundSources, $queueStats] = $this->buildQueuePayload($request, 'approver');

        return view('accounting.queue', [
            'workspace' => 'approver',
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function cashCertificationQueue(Request $request): View
    {
        [$applications, $filters, $fundSources, $queueStats] = $this->buildQueuePayload($request, 'cash_certifier');

        return view('accounting.queue', [
            'workspace' => 'cash_certifier',
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function showAccountingOfficer(Application $application): View
    {
        $application = $this->accountingOfficerBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderShowView($application, 'officer');
    }

    public function showAccountingApprover(Application $application): View
    {
        $application = $this->accountingApproverBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderShowView($application, 'approver');
    }

    public function showCashCertification(Application $application): View
    {
        $application = $this->cashCertificationBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderShowView($application, 'cash_certifier');
    }

    public function showAccountingOfficerOrs(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showAccountingOfficerDv(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showAccountingOfficerLddapAda(Application $application)
    {
        $application = $this->accountingOfficerBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function showAccountingApproverOrs(Application $application)
    {
        $application = $this->accountingApproverBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showAccountingApproverDv(Application $application)
    {
        $application = $this->accountingApproverBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showAccountingApproverLddapAda(Application $application)
    {
        $application = $this->accountingApproverBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function showCashCertificationOrs(Application $application)
    {
        $application = $this->cashCertificationBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function showCashCertificationDv(Application $application)
    {
        $application = $this->cashCertificationBaseQuery()->whereKey($application->id)->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function showCashCertificationLddapAda(Application $application)
    {
        $application = $this->cashCertificationBaseQuery()->whereKey($application->id)->firstOrFail();

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
            ];

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.accounting_review_submitted', $application, [
            'decision' => $validated['decision'],
            'accounting_remarks' => $validated['gl_accounting_remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        return redirect()
            ->route('accounting-officer.gl-payment-reviews')
            ->with('success', $validated['decision'] === 'approved'
                ? 'Case submitted to the accounting approver successfully.'
                : 'Case returned to the budget officer for compliance.');
    }

    public function submitAccountingApproverDecision(Request $request, Application $application): RedirectResponse
    {
        $application = $this->accountingApproverBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
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

        $updatePayload = [
            'gl_accounting_approval_status' => $validated['decision'],
            'gl_accounting_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_accounting_approved_by' => $request->user()->id,
            'gl_accounting_approved_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            $updatePayload['gl_payment_status'] = 'for_processing_program_amount_approval';
            $updatePayload['gl_program_amount_approval_status'] = 'pending_approval';
            $updatePayload['gl_program_amount_approval_remarks'] = null;
            $updatePayload['gl_program_amount_approved_by'] = null;
            $updatePayload['gl_program_amount_approved_at'] = null;
        } elseif ($validated['decision'] === 'for_compliance') {
            $updatePayload['gl_payment_status'] = 'for_compliance_accounting_officer';
            $updatePayload['gl_accounting_review_status'] = 'pending_review';
            $updatePayload['gl_accounting_remarks'] = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
            $updatePayload['gl_accounting_reviewed_by'] = null;
            $updatePayload['gl_accounting_reviewed_at'] = null;
            $updatePayload['gl_accounting_approval_status'] = null;
            $updatePayload['gl_accounting_approved_by'] = null;
            $updatePayload['gl_accounting_approved_at'] = null;
        } else {
            $updatePayload['gl_payment_status'] = 'for_processing_accounting';
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.accounting_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        return redirect()
            ->route('accounting-approver.gl-payment-approvals')
            ->with('success', 'Accounting approval decision saved successfully.');
    }

    public function submitCashCertificationDecision(Request $request, Application $application): RedirectResponse
    {
        $application = $this->cashCertificationBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
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

        $updatePayload = [
            'gl_cash_certification_status' => $validated['decision'],
            'gl_cash_certification_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_cash_certified_by' => $request->user()->id,
            'gl_cash_certified_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            $updatePayload['gl_payment_status'] = 'for_processing_finance_director';
            $updatePayload['gl_finance_director_status'] = 'pending_approval';
            $updatePayload['gl_finance_director_remarks'] = null;
            $updatePayload['gl_finance_director_approved_by'] = null;
            $updatePayload['gl_finance_director_approved_at'] = null;
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
            $updatePayload['gl_cash_certified_by'] = null;
            $updatePayload['gl_cash_certified_at'] = null;
        } else {
            $updatePayload['gl_payment_status'] = 'for_processing_accounting_certification';
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.cash_certification_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        return redirect()
            ->route('accounting-approver.cash-certifications')
            ->with('success', 'Cash certification decision saved successfully.');
    }

    protected function buildQueuePayload(Request $request, string $workspace): array
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
        ];

        $query = match ($workspace) {
            'officer' => $this->accountingOfficerBaseQuery(),
            'cash_certifier' => $this->cashCertificationBaseQuery(),
            default => $this->accountingApproverBaseQuery(),
        };

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
            ->latest(match ($workspace) {
                'officer' => 'gl_program_approved_at',
                'cash_certifier' => 'gl_cash_approved_at',
                default => 'gl_accounting_reviewed_at',
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSourceQuery = match ($workspace) {
            'officer' => $this->accountingOfficerBaseQuery(),
            'cash_certifier' => $this->cashCertificationBaseQuery(),
            default => $this->accountingApproverBaseQuery(),
        };

        $fundSources = $fundSourceQuery
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $statsQuery = match ($workspace) {
            'officer' => $this->accountingOfficerBaseQuery(),
            'cash_certifier' => $this->cashCertificationBaseQuery(),
            default => $this->accountingApproverBaseQuery(),
        };

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

        return [$applications, $filters, $fundSources, $queueStats];
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

    protected function accountingOfficerBaseQuery()
    {
        return $this->baseQuery()
            ->whereIn('gl_payment_status', ['for_processing_accounting', 'for_compliance_accounting_officer'])
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_accounting_review_status', 'pending_review')
                    ->orWhereNull('gl_accounting_review_status');
            });
    }

    protected function accountingApproverBaseQuery()
    {
        return $this->baseQuery()
            ->where('gl_payment_status', 'for_processing_accounting')
            ->where('gl_accounting_review_status', 'reviewed')
            ->where('gl_accounting_approval_status', 'pending_approval');
    }

    protected function cashCertificationBaseQuery()
    {
        return $this->baseQuery()
            ->where('gl_payment_status', 'for_processing_accounting_certification')
            ->where('gl_cash_approval_status', 'approved')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_cash_certification_status', 'pending_approval')
                    ->orWhereNull('gl_cash_certification_status');
            });
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
                'glBudgetReviewer',
                'glProgramApprover',
                'glAccountingReviewer',
                'glAccountingApprover',
                'glCashReviewer',
                'glCashApprover',
                'glCashCertifier',
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'));
    }
}
