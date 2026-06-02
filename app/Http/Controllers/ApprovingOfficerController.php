<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
use App\Models\ModeOfAssistance;
use App\Notifications\GuaranteeLetterApprovedNotification;
use App\Services\AuditLogService;
use App\Services\FamilyNetworkService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovingOfficerController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function __construct(
        protected FamilyNetworkService $familyNetwork,
        protected AuditLogService $auditLogs
    ) {
    }

    public function dashboard()
    {
        $eligibleApplications = $this->eligibleApplicationsQuery(auth()->user());

        $pending = (clone $eligibleApplications)
            ->where('status', 'for_approval')
            ->count();
        $officerStats = Application::query()
            ->selectRaw("SUM(CASE WHEN status = 'approved' AND DATE(updated_at) = ? THEN 1 ELSE 0 END) as approved_today", [today()->toDateString()])
            ->selectRaw("SUM(CASE WHEN status = 'denied' AND DATE(updated_at) = ? THEN 1 ELSE 0 END) as denied_today", [today()->toDateString()])
            ->selectRaw("SUM(CASE WHEN approving_officer_id = ? THEN 1 ELSE 0 END) as my_approvals", [auth()->id()])
            ->selectRaw("SUM(CASE WHEN approving_officer_id = ? AND status = 'released' AND YEAR(updated_at) = ? AND MONTH(updated_at) = ? THEN 1 ELSE 0 END) as released_this_month", [auth()->id(), now()->year, now()->month])
            ->first();

        $approvedToday = (int) ($officerStats?->approved_today ?? 0);
        $deniedToday = (int) ($officerStats?->denied_today ?? 0);
        $myApprovals = (int) ($officerStats?->my_approvals ?? 0);
        $releasedThisMonth = (int) ($officerStats?->released_this_month ?? 0);

        $trendDates = collect(range(6, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->startOfDay());
        $trendDates->push(now()->startOfDay());

        $trendStart = now()->subDays(6)->startOfDay();
        $trendRows = Application::query()
            ->selectRaw('DATE(updated_at) as day')
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied")
            ->whereDate('updated_at', '>=', $trendStart)
            ->whereIn('status', ['approved', 'denied'])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $decisionTrend = $trendDates->map(function (Carbon $date) use ($trendRows) {
            $row = $trendRows->get($date->toDateString());

            return [
                'label' => $date->format('M d'),
                'approved' => (int) ($row->approved ?? 0),
                'denied' => (int) ($row->denied ?? 0),
            ];
        });

        $decisionPeak = max(
            $decisionTrend->max('approved') ?: 0,
            $decisionTrend->max('denied') ?: 0,
            1
        );

        $statusRows = Application::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('status', ['for_approval', 'approved', 'denied', 'released'])
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusBreakdown = [
            'For Approval' => (int) ($statusRows['for_approval'] ?? 0),
            'Approved' => (int) ($statusRows['approved'] ?? 0),
            'Denied' => (int) ($statusRows['denied'] ?? 0),
            'Released' => (int) ($statusRows['released'] ?? 0),
        ];

        $recentApprovals = Application::with(['client', 'assistanceType'])
            ->whereNotNull('approving_officer_id')
            ->latest('updated_at')
            ->take(6)
            ->get();

        return view('approving-officer.dashboard', compact(
            'pending',
            'approvedToday',
            'deniedToday',
            'myApprovals',
            'releasedThisMonth',
            'decisionTrend',
            'decisionPeak',
            'statusBreakdown',
            'recentApprovals'
        ));
    }

    public function applications()
    {
        $applications = $this->eligibleApplicationsQuery(auth()->user())
            ->with(['client', 'assistanceType'])
            ->where('status', 'for_approval')
            ->latest()
            ->paginate(10);

        return view('approving-officer.applications', compact('applications'));
    }

    public function myApprovals(Request $request)
    {
        $query = Application::with(['client', 'assistanceType'])
            ->where('approving_officer_id', auth()->id())
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
            });
        }

        $applications = $query->paginate(10)->withQueryString();

        return view('approving-officer.my-approvals', compact('applications'));
    }

    public function glPaymentApprovals(Request $request)
    {
        $user = auth()->user();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->glPaymentFinishedQuery($user)
            : $this->glPaymentApprovalQuery($user, false);

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
            ->latest($user->role === 'budget_officer' ? 'gl_program_approved_at' : 'gl_budget_reviewed_at')
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

        $queueStats = [
            'total' => (clone $sourceQuery)->count(),
            'with_remarks' => (clone $sourceQuery)
                ->whereNotNull('gl_budget_remarks')
                ->where('gl_budget_remarks', '!=', '')
                ->count(),
        ];

        return view('approving-officer.gl-payment-approvals', [
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => $queueStats,
        ]);
    }

    public function glProgramAmountApprovals(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
            'payment_status' => trim((string) $request->input('payment_status', 'all')),
            'scope' => trim((string) $request->input('scope', 'active')),
        ];

        $sourceQuery = $filters['scope'] === 'finished'
            ? $this->glProgramAmountFinishedQuery()
            : $this->glProgramAmountApprovalQuery(false);

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
            ->latest('gl_accounting_approved_at')
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

        $queueStats = [
            'total' => (clone $sourceQuery)->count(),
            'with_remarks' => (clone $sourceQuery)
                ->where(function ($remarkQuery) {
                    $remarkQuery->whereNotNull('gl_budget_remarks')->where('gl_budget_remarks', '!=', '')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('gl_accounting_remarks')->where('gl_accounting_remarks', '!=', '');
                        });
                })
                ->count(),
        ];

        return view('approving-officer.gl-program-amount-approvals', [
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'paymentStatusOptions' => $paymentStatusOptions,
            'queueStats' => $queueStats,
        ]);
    }

    public function budgetOfficerDashboard()
    {
        $user = auth()->user();
        $baseQuery = $this->glPaymentApprovalQuery($user, true);
        $supportingDocsConstraint = fn ($query) => $query->where('document_type', 'Other Supporting Document');
        $amountSql = Application::effectiveDisplayedAmountSql();

        $stats = [
            'for_review' => (clone $baseQuery)->count(),
            'with_remarks' => (clone $baseQuery)->whereNotNull('gl_budget_remarks')->where('gl_budget_remarks', '!=', '')->count(),
            'with_supporting_docs' => (clone $baseQuery)->whereHas('documents', $supportingDocsConstraint)->count(),
            'total_amount' => (float) ((clone $baseQuery)->sum(DB::raw($amountSql))),
        ];

        $recentEndorsements = (clone $baseQuery)
            ->latest($user?->role === 'budget_officer' ? 'gl_program_approved_at' : 'gl_budget_reviewed_at')
            ->latest('updated_at')
            ->take(6)
            ->get();

        $fundSourceBreakdown = (clone $baseQuery)
            ->selectRaw("COALESCE(gl_finance_fund_source, 'Unspecified Fund Source') as fund_source")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM('.$amountSql.') as amount')
            ->groupBy('fund_source')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'fund_source' => $row->fund_source,
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->values();

        $providerLoad = (clone $baseQuery)
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
            ->values();

        return view('budget-officer.dashboard', [
            'workspace' => $user?->role === 'budget_approver' ? 'approver' : 'officer',
            'stats' => $stats,
            'recentEndorsements' => $recentEndorsements,
            'fundSourceBreakdown' => $fundSourceBreakdown,
            'providerLoad' => $providerLoad,
        ]);
    }

    public function showGlPaymentApproval($id)
    {
        $user = auth()->user();
        $application = $this->glPaymentApprovalQuery($user, true, true)->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        return view('approving-officer.gl-payment-approval-show', [
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

    public function showGlProgramAmountApproval($id)
    {
        $application = $this->glProgramAmountApprovalQuery(true, true)->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        return view('approving-officer.gl-program-amount-approval-show', [
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

    public function showGlFinanceOrs($id)
    {
        $application = $this->resolveGlFinanceDocumentApplication((int) $id);

        return $this->renderGlOrsView($application);
    }

    public function showGlFinanceDv($id)
    {
        $application = $this->resolveGlFinanceDocumentApplication((int) $id);

        return $this->renderGlDvView($application);
    }

    public function showGlFinanceLddapAda($id)
    {
        $application = $this->resolveGlFinanceDocumentApplication((int) $id);

        return $this->renderGlLddapAdaView($application);
    }

    public function updateGlPaymentApproval(Request $request, $id)
    {
        $user = auth()->user();
        $application = $this->glPaymentApprovalQuery($user, false)->with('modeOfAssistance')->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        if ($user->role === 'budget_officer') {
            $validated = $request->validate([
                'decision' => ['required', 'in:approved,for_compliance'],
                'remarks' => ['nullable', 'string', 'max:1500'],
            ]);

            if ($validated['decision'] === 'for_compliance' && blank($validated['remarks'] ?? null)) {
                throw ValidationException::withMessages([
                    'remarks' => 'Compliance remarks are required when returning the case to the approving officer.',
                ]);
            }

            $remarks = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
            $updatePayload = $validated['decision'] === 'approved'
                ? [
                    'gl_budget_remarks' => $remarks,
                    'gl_budget_reviewed_by' => auth()->id(),
                    'gl_budget_reviewed_at' => now(),
                    'gl_budget_approval_status' => 'pending_approval',
                    'gl_budget_approval_remarks' => null,
                    'gl_budget_approved_by' => null,
                    'gl_budget_approved_at' => null,
                    'gl_payment_status' => 'for_processing_budget',
                ]
                : [
                    'gl_budget_remarks' => $remarks,
                    'gl_budget_reviewed_by' => null,
                    'gl_budget_reviewed_at' => null,
                    'gl_budget_approval_status' => null,
                    'gl_budget_approval_remarks' => null,
                    'gl_budget_approved_by' => null,
                    'gl_budget_approved_at' => null,
                    'gl_program_approval_status' => 'for_compliance',
                    'gl_program_approval_remarks' => $remarks,
                    'gl_program_approved_by' => auth()->id(),
                    'gl_program_approved_at' => now(),
                    'gl_payment_status' => 'for_compliance_gl_processor',
                ];

            $application->update($updatePayload);

            $this->auditLogs->log($request, 'gl_payment.budget_review_submitted', $application, [
                'decision' => $validated['decision'],
                'remarks' => $validated['remarks'] ?? null,
                'payment_status' => $updatePayload['gl_payment_status'],
            ]);

            return redirect()
                ->route('budget-officer.gl-payment-approvals')
                ->with('success', $validated['decision'] === 'approved'
                    ? 'Budget review submitted to the budget approver successfully.'
                    : 'Case returned to the approving officer for compliance.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the GL payment approval.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Compliance remarks are required when returning the case to the previous stage.',
            ]);
        }

        if ($user->role === 'budget_approver') {
            $updatePayload = [
                'gl_budget_approval_status' => $validated['decision'],
                'gl_budget_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
                'gl_budget_approved_by' => auth()->id(),
                'gl_budget_approved_at' => now(),
            ];

            if ($validated['decision'] === 'approved') {
                $updatePayload['gl_payment_status'] = 'for_processing_accounting';
                $updatePayload['gl_accounting_review_status'] = 'pending_review';
                $updatePayload['gl_accounting_remarks'] = null;
                $updatePayload['gl_accounting_reviewed_by'] = null;
                $updatePayload['gl_accounting_reviewed_at'] = null;
                $updatePayload['gl_accounting_approval_status'] = null;
                $updatePayload['gl_accounting_approval_remarks'] = null;
                $updatePayload['gl_accounting_approved_by'] = null;
                $updatePayload['gl_accounting_approved_at'] = null;
            } elseif ($validated['decision'] === 'for_compliance') {
                $updatePayload['gl_payment_status'] = 'for_compliance_budget_officer';
                $updatePayload['gl_budget_remarks'] = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
                $updatePayload['gl_budget_reviewed_by'] = null;
                $updatePayload['gl_budget_reviewed_at'] = null;
                $updatePayload['gl_budget_approval_status'] = null;
                $updatePayload['gl_budget_approved_by'] = null;
                $updatePayload['gl_budget_approved_at'] = null;
            } else {
                $updatePayload['gl_payment_status'] = 'for_processing_budget';
            }

            $application->update($updatePayload);

            $this->auditLogs->log($request, 'gl_payment.budget_approval_updated', $application, [
                'decision' => $validated['decision'],
                'remarks' => $validated['remarks'] ?? null,
                'payment_status' => $updatePayload['gl_payment_status'],
            ]);

            return redirect()
                ->route('budget-approver.gl-payment-approvals')
                ->with('success', 'Budget approval decision saved successfully.');
        }

        $updatePayload = [
            'gl_program_approval_status' => $validated['decision'],
            'gl_program_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_program_approved_by' => auth()->id(),
            'gl_program_approved_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            $updatePayload['gl_payment_status'] = 'for_processing_budget';
            $updatePayload['gl_budget_remarks'] = null;
            $updatePayload['gl_budget_reviewed_by'] = null;
            $updatePayload['gl_budget_reviewed_at'] = null;
            $updatePayload['gl_budget_approval_status'] = null;
            $updatePayload['gl_budget_approval_remarks'] = null;
            $updatePayload['gl_budget_approved_by'] = null;
            $updatePayload['gl_budget_approved_at'] = null;
        } elseif ($validated['decision'] === 'for_compliance') {
            $updatePayload['gl_payment_status'] = 'for_compliance_gl_processor';
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.program_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('approving.gl-payment-approvals')
            ->with('success', 'GL payment approval decision saved successfully.');
    }

    public function updateGlProgramAmountApproval(Request $request, $id)
    {
        $application = $this->glProgramAmountApprovalQuery(false)->with('modeOfAssistance')->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the program amount approval.',
            ]);
        }

        if ($validated['decision'] === 'for_compliance' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Compliance remarks are required when returning the case to the accounting officer.',
            ]);
        }

        $updatePayload = [
            'gl_program_amount_approval_status' => $validated['decision'],
            'gl_program_amount_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_program_amount_approved_by' => auth()->id(),
            'gl_program_amount_approved_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            $updatePayload['gl_payment_status'] = 'for_processing_cash';
            $updatePayload['gl_cash_review_status'] = 'pending_review';
            $updatePayload['gl_cash_remarks'] = null;
            $updatePayload['gl_cash_reviewed_by'] = null;
            $updatePayload['gl_cash_reviewed_at'] = null;
            $updatePayload['gl_cash_approval_status'] = null;
            $updatePayload['gl_cash_approval_remarks'] = null;
            $updatePayload['gl_cash_approved_by'] = null;
            $updatePayload['gl_cash_approved_at'] = null;
        } elseif ($validated['decision'] === 'for_compliance') {
            $updatePayload['gl_payment_status'] = 'for_compliance_accounting_officer';
            $updatePayload['gl_accounting_review_status'] = 'pending_review';
            $updatePayload['gl_accounting_remarks'] = filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null;
            $updatePayload['gl_accounting_reviewed_by'] = null;
            $updatePayload['gl_accounting_reviewed_at'] = null;
            $updatePayload['gl_accounting_approval_status'] = null;
            $updatePayload['gl_accounting_approval_remarks'] = null;
            $updatePayload['gl_accounting_approved_by'] = null;
            $updatePayload['gl_accounting_approved_at'] = null;
        } else {
            $updatePayload['gl_payment_status'] = 'for_processing_program_amount_approval';
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.program_amount_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $updatePayload['gl_payment_status'],
        ]);

        return redirect()
            ->route('approving.gl-program-amount-approvals')
            ->with('success', 'Program amount approval decision saved successfully.');
    }

    public function show($id)
    {
        $readOnly = request()->boolean('readonly');
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'beneficiaryProfile',
            'documents',
            'assistanceDetail',
            'serviceProvider',
            'frequencyRule',
            'frequencyBasisApplication',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'assistanceRecommendations.referralInstitution',
            'assistanceType',
            'assistanceSubtype',
            'modeOfAssistance',
        ])->findOrFail($id);
        $this->ensureOfficerCanHandleApplication($application);

        if (is_null($application->approving_officer_id)) {
            $application->approving_officer_id = auth()->id();
            $application->save();
        }

        $householdMembers = $this->resolveHouseholdMembers($application);
        $familyNetwork = $this->familyNetwork->buildApplicationNetwork($application);

        return view('approving-officer.show', compact('application', 'readOnly', 'householdMembers', 'familyNetwork'));
    }

    protected function resolveGlFinanceDocumentApplication(int $id): Application
    {
        $user = auth()->user();

        if ($user->role === 'budget_officer' || $user->role === 'budget_approver') {
            return $this->glPaymentApprovalQuery($user, true, true)->whereKey($id)->firstOrFail();
        }

        $application = $this->glPaymentApprovalQuery($user, true, true)->whereKey($id)->first();

        if ($application) {
            return $application;
        }

        return $this->glProgramAmountApprovalQuery(true, true)->whereKey($id)->firstOrFail();
    }

    protected function glPaymentApprovalQuery($user, bool $includeHandled = false, bool $withDocuments = false)
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
                'glBudgetApprover',
                'approvingOfficer.position',
                'glBudgetApprover.position',
                'glAccountingApprover.position',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($stageQuery) use ($user, $includeHandled) {
                if ($user->role === 'budget_officer') {
                    $stageQuery->where(function ($query) {
                        $query->whereIn('gl_payment_status', ['for_processing_budget', 'for_compliance_budget_officer'])
                            ->where('gl_program_approval_status', 'approved')
                            ->whereNull('gl_budget_reviewed_at');
                    });

                    if ($includeHandled) {
                        $stageQuery->orWhere('gl_budget_reviewed_by', $user->id);
                    }

                    return;
                }

                if ($user->role === 'budget_approver') {
                    $stageQuery->where(function ($query) {
                        $query->whereIn('gl_payment_status', ['for_processing_budget', 'for_compliance_budget_officer'])
                            ->whereNotNull('gl_budget_reviewed_at')
                            ->where(function ($statusQuery) {
                                $statusQuery->where('gl_budget_approval_status', 'pending_approval')
                                    ->orWhereNull('gl_budget_approval_status');
                            });
                    });

                    if ($includeHandled) {
                        $stageQuery->orWhere('gl_budget_approved_by', $user->id);
                    }

                    return;
                }

                $stageQuery->where(function ($query) {
                    $query->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_compliance_approving_officer', 'for_processing_budget'])
                        ->where(function ($statusQuery) {
                            $statusQuery->where('gl_program_approval_status', 'pending_approval')
                                ->orWhereNull('gl_program_approval_status');
                        });
                });

                if ($includeHandled) {
                    $stageQuery->orWhere('gl_program_approved_by', $user->id);
                }
            });
    }

    protected function glProgramAmountApprovalQuery(bool $includeHandled = false, bool $withDocuments = false)
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
                'glProgramAmountApprover',
                'approvingOfficer.position',
                'glBudgetApprover.position',
                'glAccountingApprover.position',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($query) use ($includeHandled) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereIn('gl_payment_status', ['for_processing_program_amount_approval', 'for_compliance_accounting_officer'])
                        ->where(function ($inner) {
                            $inner->where('gl_program_amount_approval_status', 'pending_approval')
                                ->orWhereNull('gl_program_amount_approval_status');
                        });
                });

                if ($includeHandled) {
                    $query->orWhere('gl_program_amount_approved_by', auth()->id());
                }
            });
    }

    protected function glPaymentFinishedQuery($user)
    {
        $query = $this->glPaymentApprovalQuery($user, true);

        if ($user->role === 'budget_officer') {
            return $query->where('gl_budget_reviewed_by', $user->id);
        }

        if ($user->role === 'budget_approver') {
            return $query->where('gl_budget_approved_by', $user->id);
        }

        return $query->where('gl_program_approved_by', $user->id);
    }

    protected function glProgramAmountFinishedQuery()
    {
        return $this->glProgramAmountApprovalQuery(true)
            ->where('gl_program_amount_approved_by', auth()->id());
    }

    public function approve(Request $request, $id)
    {
        $app = Application::with(['modeOfAssistance', 'serviceProvider.accounts', 'client', 'assistanceRecommendations'])->findOrFail($id);
        $finalAmount = $app->assistanceRecommendations->isNotEmpty()
            ? $app->recommendationFinalAmountTotal()
            : (float) $request->input('final_amount', 0);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $finalAmount);
        $this->validateModeAmountRule($app, $finalAmount, 'final_amount');

        $app->final_amount = $finalAmount;
        $app->approving_officer_id = auth()->id();
        $app->status = 'approved';
        $app->denial_reason = null;
        $app->save();

        if (
            strtolower((string) ($app->modeOfAssistance?->name ?? $app->mode_of_assistance)) === 'guarantee letter'
            && $app->serviceProvider?->accounts?->isNotEmpty()
        ) {
            foreach ($app->serviceProvider->accounts as $account) {
                $account->notify(new GuaranteeLetterApprovedNotification($app));
            }
        }

        $this->auditLogs->log($request, 'application.approved', $app, [
            'reference_no' => $app->reference_no,
            'final_amount' => $app->final_amount,
        ]);

        return redirect()
            ->route('approving.applications')
            ->with('success', 'Application approved successfully.');
    }

    public function updateRecommendation(Request $request, $applicationId, $recommendationId)
    {
        $application = Application::with('assistanceRecommendations')->findOrFail($applicationId);
        $this->ensureRecommendationCanBeEdited($application);

        $validated = $request->validate([
            'final_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $recommendation = $application->assistanceRecommendations()
            ->with('modeOfAssistance')
            ->findOrFail($recommendationId);

        $this->validateRecommendationModeAmountRule(
            $recommendation->modeOfAssistance,
            (float) $validated['final_amount'],
            'final_amount'
        );

        DB::transaction(function () use ($application, $recommendation, $validated) {
            $recommendation->update([
                'final_amount' => $validated['final_amount'],
            ]);

            $application->load('assistanceRecommendations');
            $application->syncFinalAmountFromRecommendations();
        });

        $this->auditLogs->log($request, 'recommendation.updated', $recommendation, [
            'application_id' => $application->id,
            'final_amount' => $validated['final_amount'],
        ]);

        return redirect()
            ->route('approving.show', ['id' => $application->id, 'tab' => 'recommendation'])
            ->with('success', 'Assistance amount updated successfully.');
    }

    public function destroyRecommendation($applicationId, $recommendationId)
    {
        $application = Application::with('assistanceRecommendations')->findOrFail($applicationId);
        $this->ensureRecommendationCanBeEdited($application);

        $recommendation = $application->assistanceRecommendations()->findOrFail($recommendationId);

        DB::transaction(function () use ($application, $recommendation) {
            $recommendation->delete();

            $application->load('assistanceRecommendations');
            $application->syncFinalAmountFromRecommendations();
        });

        $this->auditLogs->log(request(), 'recommendation.deleted', $application, [
            'recommendation_id' => $recommendationId,
        ]);

        return redirect()
            ->route('approving.show', ['id' => $application->id, 'tab' => 'recommendation'])
            ->with('success', 'Assistance item removed successfully.');
    }

    public function deny(Request $request, $id)
    {
        $app = Application::findOrFail($id);
        $this->ensureOfficerCanHandleApplication($app);

        $app->approving_officer_id = auth()->id();
        $app->status = 'denied';
        $app->denial_reason = $request->denial_reason;
        $app->save();
        $this->auditLogs->log($request, 'application.denied', $app, [
            'reference_no' => $app->reference_no,
            'denial_reason' => $app->denial_reason,
        ]);

        return redirect()
            ->route('approving.applications')
            ->with('success', 'Application denied successfully.');
    }

    public function certificate($id)
    {
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'serviceProvider',
            'frequencyRule',
            'frequencyBasisApplication',
            'documents',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.referralInstitution',
            'socialWorker',
            'approvingOfficer',
        ])->findOrFail($id);

        if (! in_array($application->status, ['approved', 'released'], true)) {
            abort(403, 'Certificate available only for approved or released applications.');
        }

        return view('social-worker.certificate', compact('application'));
    }

    public function guaranteeLetter($id)
    {
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'serviceProvider',
            'documents',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'assistanceRecommendations.referralInstitution',
            'socialWorker',
            'approvingOfficer',
        ])->findOrFail($id);

        if (! in_array($application->status, ['approved', 'released'], true)) {
            abort(403, 'Guarantee Letter is available only for approved or released applications.');
        }

        if (strtolower((string) ($application->modeOfAssistance?->name ?? $application->mode_of_assistance)) !== 'guarantee letter') {
            abort(403, 'Guarantee Letter printing is only available when the mode of assistance is Guarantee Letter.');
        }

        return view('social-worker.guarantee-letter', compact('application'));
    }

    protected function validateModeAmountRule(Application $application, float $amount, string $field): void
    {
        $this->validateRecommendationModeAmountRule($application->modeOfAssistance, $amount, $field);
    }

    protected function validateRecommendationModeAmountRule(?ModeOfAssistance $mode, float $amount, string $field): void
    {
        $minimumAmount = $mode?->minimum_amount !== null
            ? (float) $mode->minimum_amount
            : null;
        $maximumAmount = $mode?->maximum_amount !== null
            ? (float) $mode->maximum_amount
            : null;

        if ($minimumAmount !== null && $amount < $minimumAmount) {
            throw ValidationException::withMessages([
                $field => 'This mode of assistance requires at least PHP '.number_format($minimumAmount, 2).'.',
            ]);
        }

        if ($maximumAmount !== null && $amount > $maximumAmount) {
            throw ValidationException::withMessages([
                $field => 'This mode of assistance only allows amounts up to PHP '.number_format($maximumAmount, 2).'.',
            ]);
        }
    }

    protected function ensureRecommendationCanBeEdited(Application $application): void
    {
        if ($application->status !== 'for_approval') {
            abort(403, 'Recommendations can only be changed while the application is pending approval.');
        }
    }

    protected function eligibleApplicationsQuery(User $officer)
    {
        $range = $this->resolveOfficerRange($officer);

        if ($range === null) {
            return Application::query()->whereRaw('1 = 0');
        }

        $amountExpression = $this->approvalAmountSql();
        $query = Application::query()
            ->whereRaw("{$amountExpression} >= ?", [$range['min']]);

        if ($range['max'] !== null) {
            $query->whereRaw("{$amountExpression} <= ?", [$range['max']]);
        }

        return $query;
    }

    protected function ensureOfficerCanHandleApplication(Application $application): void
    {
        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());
    }

    protected function ensureOfficerCanHandleAmount(?User $officer, float $amount): void
    {
        $range = $officer ? $this->resolveOfficerRange($officer) : null;

        if ($range === null) {
            abort(403, 'No approval amount range is assigned to your account.');
        }

        if ($amount < $range['min']) {
            abort(403, 'This application amount is below your approval range.');
        }

        if ($range['max'] !== null && $amount > $range['max']) {
            abort(403, 'This application amount exceeds your approval range.');
        }
    }

    protected function resolveOfficerRange(User $officer): ?array
    {
        if (! in_array($officer->role, ['approving_officer', 'budget_officer', 'budget_approver'], true)) {
            return null;
        }

        return [
            'min' => $officer->approval_min_amount !== null ? (float) $officer->approval_min_amount : 0.0,
            'max' => $officer->approval_max_amount !== null ? (float) $officer->approval_max_amount : null,
        ];
    }

    protected function approvalAmountSql(): string
    {
        return <<<SQL
CASE
    WHEN EXISTS (
        SELECT 1
        FROM application_assistance_recommendations aar_exists
        WHERE aar_exists.application_id = applications.id
    ) THEN COALESCE((
        SELECT SUM(aar_sum.final_amount)
        FROM application_assistance_recommendations aar_sum
        WHERE aar_sum.application_id = applications.id
    ), 0)
    ELSE COALESCE(applications.final_amount, applications.recommended_amount, applications.amount_needed, 0)
END
SQL;
    }

    protected function resolveHouseholdMembers(Application $application)
    {
        $snapshotMembers = $application->applicationFamilyMembers()
            ->with('relationshipData')
            ->orderBy('id')
            ->get();

        if ($snapshotMembers->isNotEmpty()) {
            return $snapshotMembers;
        }

        if ($application->usesBeneficiaryHousehold() && $application->beneficiaryProfile) {
            return $application->beneficiaryProfile
                ->familyMembers()
                ->whereNull('application_id')
                ->with('relationshipData')
                ->orderBy('id')
                ->get();
        }

        return $application->client
            ->familyMembers()
            ->whereNull('beneficiary_profile_id')
            ->whereNull('application_id')
            ->with('relationshipData')
            ->orderBy('id')
            ->get();
    }
}
