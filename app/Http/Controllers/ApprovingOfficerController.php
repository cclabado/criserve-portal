<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class ApprovingOfficerController extends Controller
{
    public function dashboard()
    {
        $pending = Application::where('status', 'for_approval')->count();
        $approvedToday = Application::where('status', 'approved')
            ->whereDate('updated_at', today())
            ->count();

        $deniedToday = Application::where('status', 'denied')
            ->whereDate('updated_at', today())
            ->count();

        return view('approving-officer.dashboard', compact(
            'pending',
            'approvedToday',
            'deniedToday'
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

    public function show($id)
    {
        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'documents',
            'assistanceType',
            'assistanceSubtype'
        ])->findOrFail($id);

        return view('approving-officer.show', compact('application'));
    }

    public function approve(Request $request, $id)
    {
        $app = Application::findOrFail($id);

        $app->final_amount = $request->final_amount;
        $app->status = 'approved';
        $app->denial_reason = null;
        $app->save();

        return redirect()
            ->route('approving.applications')
            ->with('success', 'Application approved successfully.');
    }

    public function deny(Request $request, $id)
    {
        $app = Application::findOrFail($id);

        $app->status = 'denied';
        $app->denial_reason = $request->denial_reason;
        $app->save();

        return redirect()
            ->route('approving.applications')
            ->with('success', 'Application denied successfully.');
    }
}