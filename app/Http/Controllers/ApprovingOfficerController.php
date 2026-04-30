<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Notifications\GuaranteeLetterApprovedNotification;
use App\Services\FamilyNetworkService;
use Illuminate\Validation\ValidationException;

class ApprovingOfficerController extends Controller
{
    public function __construct(
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    public function dashboard()
    {
        $pending = Application::where('status', 'for_approval')->count();
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
        $applications = Application::with(['client', 'assistanceType'])
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
        $app = Application::with(['modeOfAssistance', 'serviceProvider.accounts', 'client'])->findOrFail($id);
        $finalAmount = (float) $request->input('final_amount', 0);

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

    public function deny(Request $request, $id)
    {
        $app = Application::findOrFail($id);

        $app->approving_officer_id = auth()->id();
        $app->status = 'denied';
        $app->denial_reason = $request->denial_reason;
        $app->save();

        return redirect()
            ->route('approving.applications')
            ->with('success', 'Application denied successfully.');
    }

    protected function validateModeAmountRule(Application $application, float $amount, string $field): void
    {
        $minimumAmount = $application->modeOfAssistance?->minimum_amount !== null
            ? (float) $application->modeOfAssistance->minimum_amount
            : null;
        $maximumAmount = $application->modeOfAssistance?->maximum_amount !== null
            ? (float) $application->modeOfAssistance->maximum_amount
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
