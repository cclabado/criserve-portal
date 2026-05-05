<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ModeOfAssistance;
use App\Notifications\GuaranteeLetterApprovedNotification;
use App\Services\FamilyNetworkService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovingOfficerController extends Controller
{
    public function __construct(
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    public function dashboard()
    {
        $eligibleApplications = $this->eligibleApplicationsQuery(auth()->user());

        $pending = (clone $eligibleApplications)
            ->where('status', 'for_approval')
            ->count();
        $approvedToday = Application::where('status', 'approved')
            ->whereDate('updated_at', today())
            ->count();

        $deniedToday = Application::where('status', 'denied')
            ->whereDate('updated_at', today())
            ->count();

        $myApprovals = Application::where('approving_officer_id', auth()->id())->count();
        $releasedThisMonth = Application::where('approving_officer_id', auth()->id())
            ->where('status', 'released')
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->count();

        $trendDates = collect(range(6, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->startOfDay());
        $trendDates->push(now()->startOfDay());

        $decisionTrend = $trendDates->map(function (Carbon $date) {
            return [
                'label' => $date->format('M d'),
                'approved' => Application::where('status', 'approved')->whereDate('updated_at', $date)->count(),
                'denied' => Application::where('status', 'denied')->whereDate('updated_at', $date)->count(),
            ];
        });

        $decisionPeak = max(
            $decisionTrend->max('approved') ?: 0,
            $decisionTrend->max('denied') ?: 0,
            1
        );

        $statusBreakdown = [
            'For Approval' => Application::where('status', 'for_approval')->count(),
            'Approved' => Application::where('status', 'approved')->count(),
            'Denied' => Application::where('status', 'denied')->count(),
            'Released' => Application::where('status', 'released')->count(),
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
