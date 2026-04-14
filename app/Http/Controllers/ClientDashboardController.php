<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class ClientDashboardController extends Controller
{

    public function index(Request $request)
    {
        $query = Application::with(['assistanceType', 'assistanceSubtype'])
            ->where('user_id', auth()->id());

        // FILTER: STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER: TYPE
        if ($request->filled('type')) {
            $query->where('assistance_type_id', $request->type);
        }

        $applications = $query->latest()->get();

        // IMPORTANT: latest APPLICATION regardless of filter
        $latestApplication = Application::where('user_id', auth()->id())
            ->latest()
            ->first();

        $types = \App\Models\AssistanceType::all();

        return view('client.dashboard', compact(
            'applications',
            'latestApplication',
            'types'
        ));
    }
    public function show($id)
    {
        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'documents',
            'assistanceType',
            'assistanceSubtype'
        ])->where('user_id', auth()->id())
        ->findOrFail($id);

        return view('client.application-details', compact('application'));
    }
}