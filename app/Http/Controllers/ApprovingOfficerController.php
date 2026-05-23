<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
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
        ];

        $query = Application::with([
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
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($stageQuery) use ($user) {
                if ($user->role === 'budget_officer') {
                    $stageQuery->where('gl_payment_status', 'for_processing_budget')
                        ->where('gl_program_approval_status', 'approved');

                    return;
                }

                $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                    ->where(function ($statusQuery) {
                        $statusQuery->where('gl_program_approval_status', 'pending_approval')
                            ->orWhereNull('gl_program_approval_status');
                    });
            });

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
            ->latest('gl_budget_reviewed_at')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = Application::query()
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($stageQuery) use ($user) {
                if ($user->role === 'budget_officer') {
                    $stageQuery->where('gl_payment_status', 'for_processing_budget')
                        ->where('gl_program_approval_status', 'approved');

                    return;
                }

                $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                    ->where(function ($statusQuery) {
                        $statusQuery->where('gl_program_approval_status', 'pending_approval')
                            ->orWhereNull('gl_program_approval_status');
                    });
            })
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $queueStats = [
            'total' => Application::query()
                ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
                ->where(function ($stageQuery) use ($user) {
                    if ($user->role === 'budget_officer') {
                        $stageQuery->where('gl_payment_status', 'for_processing_budget')
                            ->where('gl_program_approval_status', 'approved');

                        return;
                    }

                    $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                        ->where(function ($statusQuery) {
                            $statusQuery->where('gl_program_approval_status', 'pending_approval')
                                ->orWhereNull('gl_program_approval_status');
                        });
                })
                ->count(),
            'with_remarks' => Application::query()
                ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
                ->where(function ($stageQuery) use ($user) {
                    if ($user->role === 'budget_officer') {
                        $stageQuery->where('gl_payment_status', 'for_processing_budget')
                            ->where('gl_program_approval_status', 'approved');

                        return;
                    }

                    $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                        ->where(function ($statusQuery) {
                            $statusQuery->where('gl_program_approval_status', 'pending_approval')
                                ->orWhereNull('gl_program_approval_status');
                        });
                })
                ->whereNotNull('gl_budget_remarks')
                ->where('gl_budget_remarks', '!=', '')
                ->count(),
        ];

        return view('approving-officer.gl-payment-approvals', [
            'applications' => $applications,
            'filters' => $filters,
            'fundSources' => $fundSources,
            'queueStats' => $queueStats,
        ]);
    }

    public function glProgramAmountApprovals(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'fund_source' => trim((string) $request->input('fund_source', 'all')),
        ];

        $query = Application::with([
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
                'glProgramAmountApprover',
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                    ->orWhereNull('gl_program_amount_approval_status');
            });

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
            ->latest('gl_accounting_approved_at')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $fundSources = Application::query()
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                    ->orWhereNull('gl_program_amount_approval_status');
            })
            ->whereNotNull('gl_finance_fund_source')
            ->distinct()
            ->orderBy('gl_finance_fund_source')
            ->pluck('gl_finance_fund_source');

        $queueStats = [
            'total' => Application::query()
                ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
                ->where('gl_payment_status', 'for_processing_program_amount_approval')
                ->where(function ($statusQuery) {
                    $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                        ->orWhereNull('gl_program_amount_approval_status');
                })
                ->count(),
            'with_remarks' => Application::query()
                ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
                ->where('gl_payment_status', 'for_processing_program_amount_approval')
                ->where(function ($statusQuery) {
                    $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                        ->orWhereNull('gl_program_amount_approval_status');
                })
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
            'queueStats' => $queueStats,
        ]);
    }

    public function budgetOfficerDashboard()
    {
        $applications = Application::with([
                'client',
                'serviceProvider',
                'documents',
            ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_budget')
            ->where('gl_program_approval_status', 'approved')
            ->latest('gl_program_approved_at')
            ->latest('updated_at')
            ->get();

        $stats = [
            'for_review' => $applications->count(),
            'with_remarks' => $applications->filter(fn (Application $application) => filled($application->gl_budget_remarks))->count(),
            'with_supporting_docs' => $applications->filter(function (Application $application) {
                return $application->documents->contains(fn ($document) => $document->document_type === 'Other Supporting Document');
            })->count(),
            'total_amount' => $applications->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
        ];

        $recentEndorsements = $applications
            ->sortByDesc(fn (Application $application) => $application->gl_program_approved_at ?? $application->updated_at)
            ->take(6)
            ->values();

        $fundSourceBreakdown = $applications
            ->groupBy(fn (Application $application) => $application->gl_finance_fund_source ?: 'Unspecified Fund Source')
            ->map(function ($group, $fundSource) {
                return [
                    'fund_source' => $fundSource,
                    'total' => $group->count(),
                    'amount' => $group->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
                ];
            })
            ->sortByDesc('total')
            ->take(6)
            ->values();

        $providerLoad = $applications
            ->groupBy(fn (Application $application) => $application->serviceProvider?->name ?? 'Unassigned Provider')
            ->map(function ($group, $providerName) {
                return [
                    'provider' => $providerName,
                    'total' => $group->count(),
                    'amount' => $group->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0)),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        return view('budget-officer.dashboard', [
            'stats' => $stats,
            'recentEndorsements' => $recentEndorsements,
            'fundSourceBreakdown' => $fundSourceBreakdown,
            'providerLoad' => $providerLoad,
        ]);
    }

    public function showGlPaymentApproval($id)
    {
        $user = auth()->user();
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'documents',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'serviceProvider',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'glBudgetReviewer',
            'glProgramApprover',
        ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($stageQuery) use ($user) {
                if ($user->role === 'budget_officer') {
                    $stageQuery->where('gl_payment_status', 'for_processing_budget')
                        ->where('gl_program_approval_status', 'approved');

                    return;
                }

                $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                    ->where(function ($statusQuery) {
                        $statusQuery->where('gl_program_approval_status', 'pending_approval')
                            ->orWhereNull('gl_program_approval_status');
                    });
            })
            ->findOrFail($id);

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
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'documents',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'serviceProvider',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'glBudgetReviewer',
            'glProgramApprover',
            'glAccountingReviewer',
            'glAccountingApprover',
            'glProgramAmountApprover',
        ])
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                    ->orWhereNull('gl_program_amount_approval_status');
            })
            ->findOrFail($id);

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

    public function updateGlPaymentApproval(Request $request, $id)
    {
        $user = auth()->user();
        $application = Application::with('modeOfAssistance')
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where(function ($stageQuery) use ($user) {
                if ($user->role === 'budget_officer') {
                    $stageQuery->where('gl_payment_status', 'for_processing_budget')
                        ->where('gl_program_approval_status', 'approved');

                    return;
                }

                $stageQuery->whereIn('gl_payment_status', ['for_processing_program_approval', 'for_processing_budget'])
                    ->where(function ($statusQuery) {
                        $statusQuery->where('gl_program_approval_status', 'pending_approval')
                            ->orWhereNull('gl_program_approval_status');
                    });
            })
            ->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        $validated = $request->validate([
            'decision' => ['required', 'in:for_compliance,approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the GL payment approval.',
            ]);
        }

        $updatePayload = [
            'gl_program_approval_status' => $validated['decision'],
            'gl_program_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_program_approved_by' => auth()->id(),
            'gl_program_approved_at' => now(),
        ];

        if ($validated['decision'] === 'approved') {
            if ($user->role === 'budget_officer') {
                $updatePayload['gl_payment_status'] = 'for_processing_accounting';
                $updatePayload['gl_accounting_review_status'] = 'pending_review';
                $updatePayload['gl_accounting_remarks'] = null;
                $updatePayload['gl_accounting_reviewed_by'] = null;
                $updatePayload['gl_accounting_reviewed_at'] = null;
                $updatePayload['gl_accounting_approval_status'] = null;
                $updatePayload['gl_accounting_approval_remarks'] = null;
                $updatePayload['gl_accounting_approved_by'] = null;
                $updatePayload['gl_accounting_approved_at'] = null;
            } else {
                $updatePayload['gl_payment_status'] = 'for_processing_budget';
            }
        }

        $application->update($updatePayload);

        $this->auditLogs->log($request, 'gl_payment.program_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route($user->role === 'budget_officer' ? 'budget-officer.gl-payment-approvals' : 'approving.gl-payment-approvals')
            ->with('success', 'GL payment approval decision saved successfully.');
    }

    public function updateGlProgramAmountApproval(Request $request, $id)
    {
        $application = Application::with('modeOfAssistance')
            ->whereHas('modeOfAssistance', fn ($modeQuery) => $modeQuery->where('name', 'Guarantee Letter'))
            ->where('gl_payment_status', 'for_processing_program_amount_approval')
            ->where(function ($statusQuery) {
                $statusQuery->where('gl_program_amount_approval_status', 'pending_approval')
                    ->orWhereNull('gl_program_amount_approval_status');
            })
            ->findOrFail($id);

        $this->ensureOfficerCanHandleAmount(auth()->user(), $application->approvalRoutingAmount());

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,disapproved'],
            'remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($validated['decision'] === 'disapproved' && blank($validated['remarks'] ?? null)) {
            throw ValidationException::withMessages([
                'remarks' => 'Disapproval remarks are required when disapproving the program amount approval.',
            ]);
        }

        $application->update([
            'gl_program_amount_approval_status' => $validated['decision'],
            'gl_program_amount_approval_remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'gl_program_amount_approved_by' => auth()->id(),
            'gl_program_amount_approved_at' => now(),
            'gl_payment_status' => $validated['decision'] === 'approved' ? 'for_processing_cash' : 'for_processing_program_amount_approval',
            'gl_cash_review_status' => $validated['decision'] === 'approved' ? 'pending_review' : null,
            'gl_cash_remarks' => null,
            'gl_cash_reviewed_by' => null,
            'gl_cash_reviewed_at' => null,
            'gl_cash_approval_status' => null,
            'gl_cash_approval_remarks' => null,
            'gl_cash_approved_by' => null,
            'gl_cash_approved_at' => null,
        ]);

        $this->auditLogs->log($request, 'gl_payment.program_amount_approval_updated', $application, [
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? null,
            'payment_status' => $validated['decision'] === 'approved' ? 'for_processing_cash' : 'for_processing_program_amount_approval',
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
        if (! in_array($officer->role, ['approving_officer', 'budget_officer'], true)) {
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
