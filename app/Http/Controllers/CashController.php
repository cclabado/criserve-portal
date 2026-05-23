<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function cashOfficerDashboard(): View
    {
        $applications = $this->cashOfficerBaseQuery()
            ->latest('gl_program_amount_approved_at')
            ->latest('updated_at')
            ->get();

        return view('cash.dashboard', [
            'workspace' => 'officer',
            'stats' => [
                'total' => $applications->count(),
                'with_previous_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_accounting_remarks))->count(),
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

    public function cashApproverDashboard(): View
    {
        $applications = $this->cashApproverBaseQuery()
            ->latest('gl_cash_reviewed_at')
            ->latest('updated_at')
            ->get();

        return view('cash.dashboard', [
            'workspace' => 'approver',
            'stats' => [
                'total' => $applications->count(),
                'with_previous_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_cash_remarks))->count(),
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

    public function cashOfficerQueue(Request $request): View
    {
        [$applications, $filters, $fundSources, $queueStats] = $this->buildQueuePayload($request, 'officer');

        return view('cash.queue', [
            'workspace' => 'officer',
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function cashApproverQueue(Request $request): View
    {
        [$applications, $filters, $fundSources, $queueStats] = $this->buildQueuePayload($request, 'approver');

        return view('cash.queue', [
            'workspace' => 'approver',
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function showCashOfficer(Application $application): View
    {
        $application = $this->cashOfficerBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderShowView($application, 'officer');
    }

    public function showCashApprover(Application $application): View
    {
        $application = $this->cashApproverBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderShowView($application, 'approver');
    }

    public function submitCashOfficerReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->cashOfficerBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'gl_cash_remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        $application->update([
            'gl_cash_review_status' => 'reviewed',
            'gl_cash_remarks' => filled($validated['gl_cash_remarks'] ?? null) ? trim((string) $validated['gl_cash_remarks']) : null,
            'gl_cash_reviewed_by' => $request->user()->id,
            'gl_cash_reviewed_at' => now(),
            'gl_cash_approval_status' => 'pending_approval',
            'gl_cash_approval_remarks' => null,
            'gl_cash_approved_by' => null,
            'gl_cash_approved_at' => null,
            'gl_payment_status' => 'for_processing_cash',
        ]);

        $this->auditLogs->log($request, 'gl_payment.cash_review_submitted', $application, [
            'cash_remarks' => $validated['gl_cash_remarks'] ?? null,
            'payment_status' => 'for_processing_cash',
        ]);

        return redirect()
            ->route('cash-officer.gl-payment-reviews')
            ->with('success', 'Case submitted to the cash approver successfully.');
    }

    public function submitCashApproverDecision(Request $request, Application $application): RedirectResponse
    {
        $application = $this->cashApproverBaseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the cash review.',
            ]);
        }

        $application->update([
            'gl_cash_approval_status' => $validated['decision'],
            'gl_cash_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_cash_approved_by' => $request->user()->id,
            'gl_cash_approved_at' => now(),
            'gl_cash_certification_status' => $validated['decision'] === 'approved' ? 'pending_approval' : null,
            'gl_cash_certification_remarks' => null,
            'gl_cash_certified_by' => null,
            'gl_cash_certified_at' => null,
            'gl_payment_status' => $validated['decision'] === 'approved'
                ? 'for_processing_accounting_certification'
                : 'for_processing_cash',
        ]);

        $this->auditLogs->log($request, 'gl_payment.cash_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $validated['decision'] === 'approved'
                ? 'for_processing_accounting_certification'
                : 'for_processing_cash',
        ]);

        return redirect()
            ->route('cash-approver.gl-payment-approvals')
            ->with('success', 'Cash approval decision saved successfully.');
    }

    protected function buildQueuePayload(Request $request, string $workspace): array
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
        ];

        $query = $workspace === 'officer'
            ? $this->cashOfficerBaseQuery()
            : $this->cashApproverBaseQuery();

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
            ->latest($workspace === 'officer' ? 'gl_program_amount_approved_at' : 'gl_cash_reviewed_at')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = ($workspace === 'officer' ? $this->cashOfficerBaseQuery() : $this->cashApproverBaseQuery())
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $statsQuery = $workspace === 'officer' ? $this->cashOfficerBaseQuery() : $this->cashApproverBaseQuery();

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

        return [$applications, $filters, $fundSources, $queueStats];
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

    protected function cashOfficerBaseQuery()
    {
        return $this->baseQuery()
            ->where('gl_payment_status', 'for_processing_cash')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_cash_review_status', 'pending_review')
                    ->orWhereNull('gl_cash_review_status');
            });
    }

    protected function cashApproverBaseQuery()
    {
        return $this->baseQuery()
            ->where('gl_payment_status', 'for_processing_cash')
            ->where('gl_cash_review_status', 'reviewed')
            ->where('gl_cash_approval_status', 'pending_approval');
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
                'glProgramAmountApprover',
                'glAccountingReviewer',
                'glAccountingApprover',
                'glCashReviewer',
                'glCashApprover',
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'));
    }
}
