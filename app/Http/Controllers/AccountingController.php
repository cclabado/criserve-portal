<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountingController extends Controller
{
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
                'total_amount' => $applications->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
            ],
            'recentCases' => $applications->take(6)->values(),
            'providerLoad' => $applications
                ->groupBy(fn (Application $application) => $application->serviceProvider?->name ?? 'Unassigned Provider')
                ->map(fn ($group, $providerName) => [
                    'provider' => $providerName,
                    'total' => $group->count(),
                    'amount' => $group->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
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
                'total_amount' => $applications->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
                'cash_certifications_total' => $cashCertificationApplications->count(),
            ],
            'recentCases' => $applications->take(6)->values(),
            'cashCertificationCases' => $cashCertificationApplications->take(6)->values(),
            'providerLoad' => $applications
                ->groupBy(fn (Application $application) => $application->serviceProvider?->name ?? 'Unassigned Provider')
                ->map(fn ($group, $providerName) => [
                    'provider' => $providerName,
                    'total' => $group->count(),
                    'amount' => $group->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
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

    public function submitAccountingOfficerReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->accountingOfficerBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'gl_accounting_remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        $application->update([
            'gl_accounting_review_status' => 'reviewed',
            'gl_accounting_remarks' => filled($validated['gl_accounting_remarks'] ?? null) ? trim((string) $validated['gl_accounting_remarks']) : null,
            'gl_accounting_reviewed_by' => $request->user()->id,
            'gl_accounting_reviewed_at' => now(),
            'gl_accounting_approval_status' => 'pending_approval',
            'gl_accounting_approval_remarks' => null,
            'gl_accounting_approved_by' => null,
            'gl_accounting_approved_at' => null,
            'gl_payment_status' => 'for_processing_accounting',
        ]);

        $this->auditLogs->log($request, 'gl_payment.accounting_review_submitted', $application, [
            'accounting_remarks' => $validated['gl_accounting_remarks'] ?? null,
            'payment_status' => 'for_processing_accounting',
        ]);

        return redirect()
            ->route('accounting-officer.gl-payment-reviews')
            ->with('success', 'Case submitted to the accounting approver successfully.');
    }

    public function submitAccountingApproverDecision(Request $request, Application $application): RedirectResponse
    {
        $application = $this->accountingApproverBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the accounting review.',
            ]);
        }

        $application->update([
            'gl_accounting_approval_status' => $validated['decision'],
            'gl_accounting_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_accounting_approved_by' => $request->user()->id,
            'gl_accounting_approved_at' => now(),
            'gl_payment_status' => $validated['decision'] === 'approved' ? 'for_processing_program_amount_approval' : 'for_processing_accounting',
            'gl_program_amount_approval_status' => $validated['decision'] === 'approved' ? 'pending_approval' : null,
            'gl_program_amount_approval_remarks' => null,
            'gl_program_amount_approved_by' => null,
            'gl_program_amount_approved_at' => null,
        ]);

        $this->auditLogs->log($request, 'gl_payment.accounting_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $validated['decision'] === 'approved' ? 'for_processing_program_amount_approval' : 'for_processing_accounting',
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
            'decision' => ['required', 'in:approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the cash certification.',
            ]);
        }

        $application->update([
            'gl_cash_certification_status' => $validated['decision'],
            'gl_cash_certification_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_cash_certified_by' => $request->user()->id,
            'gl_cash_certified_at' => now(),
            'gl_payment_status' => 'for_processing_accounting_certification',
        ]);

        $this->auditLogs->log($request, 'gl_payment.cash_certification_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => 'for_processing_accounting_certification',
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
            ->where('gl_payment_status', 'for_processing_accounting')
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
