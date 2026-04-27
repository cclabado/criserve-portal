<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Application;

class ClientDashboardController extends Controller
{
    protected function applicationQuery()
    {
        $userId = auth()->id();

        return Application::with(['assistanceType', 'assistanceSubtype', 'assistanceDetail', 'frequencyRule'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('beneficiaryProfile', function ($beneficiaryProfileQuery) use ($userId) {
                        $beneficiaryProfileQuery->where('linked_user_id', $userId);
                    })
                    ->orWhereHas('applicationFamilyMembers', function ($familyQuery) use ($userId) {
                        $familyQuery->where('linked_user_id', $userId);
                    });
            });
    }

    protected function applyApplicationFilters(Request $request, $query)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('assistance_type_id', $request->type);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $applications = $this->applyApplicationFilters($request, $this->applicationQuery())
            ->latest()
            ->paginate(5)
            ->withQueryString();

        // IMPORTANT: latest APPLICATION regardless of filter
        $latestApplication = $this->applicationQuery()
            ->latest()
            ->first();

        $baseQuery = Application::query()->where('user_id', auth()->id());

        $statusSummary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['submitted', 'under_review', 'for_approval', 'approved'])->count(),
            'released' => (clone $baseQuery)->where('status', 'released')->count(),
            'cancelled' => (clone $baseQuery)->whereIn('status', ['cancelled', 'denied'])->count(),
        ];

        $trendStart = Carbon::today()->subDays(6);
        $dailyTrendRows = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', $trendStart)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trendLabels = [];
        $trendValues = [];

        for ($date = $trendStart->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();
            $trendLabels[] = $date->format('M d');
            $trendValues[] = (int) ($dailyTrendRows[$key]->total ?? 0);
        }

        $maxTrendValue = max(1, ...$trendValues);

        $statusBreakdown = [
            'Submitted' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'Under Review' => (clone $baseQuery)->where('status', 'under_review')->count(),
            'For Approval' => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'Released' => (clone $baseQuery)->where('status', 'released')->count(),
        ];

        $types = \App\Models\AssistanceType::all();
        return view('client.dashboard', compact(
            'applications',
            'latestApplication',
            'types',
            'statusSummary',
            'trendLabels',
            'trendValues',
            'maxTrendValue',
            'statusBreakdown'
        ));
    }

    public function applications(Request $request)
    {
        $applications = $this->applyApplicationFilters($request, $this->applicationQuery())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $types = \App\Models\AssistanceType::all();

        return view('client.applications', compact('applications', 'types'));
    }

    public function show($id)
    {
        $application = $this->applicationQuery()->with([
            'client',
            'beneficiary',
            'familyMembers',
            'documents',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'frequencyRule',
            'frequencyBasisApplication',
            'modeOfAssistance',
        ])->findOrFail($id);

        return view('client.application-details', compact('application'));
    }
}
