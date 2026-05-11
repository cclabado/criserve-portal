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
        if ($officer->role !== 'approving_officer') {
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
