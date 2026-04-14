<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Relationship;
use App\Models\AssistanceType;
use App\Models\AssistanceSubtype;

class SocialWorkerController extends Controller
{
    public function dashboard()
    {
        $totalPending = \App\Models\Application::where('status', 'submitted')->count();

        $approvedToday = \App\Models\Application::where('status', 'approved')
            ->whereDate('updated_at', today())
            ->count();

        $urgent = \App\Models\Application::where('status', 'under_review')->count();

        $totalHandled = \App\Models\Application::count();

        return view('social-worker.dashboard', compact(
            'totalPending',
            'approvedToday',
            'urgent',
            'totalHandled'
        ));
    }

    public function applications(Request $request)
    {
        $query = Application::with(['client', 'assistanceType'])->latest();

        // 🔍 FILTER: STATUS
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // 🔍 FILTER: TYPE
        if ($request->type && $request->type != 'all') {
            $query->where('assistance_type_id', $request->type);
        }

        // 🔍 SEARCH (name or reference)
        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // reference
                $q->where('reference_no', 'like', "%$search%");

                // name search
                $q->orWhereHas('client', function ($c) use ($search) {
                    $c->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
                });

            });
        }

        // 🔥 DATE FILTER (FIXED)
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // ✅ PAGINATION
        $applications = $query->paginate(5)->withQueryString();

        return view('social-worker.applications', compact('applications'));
    }

    public function show($id)
    {
        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers'
        ])->findOrFail($id);

        return view('social-worker.show', compact('application'));
    }

    public function assess($id)
    {
        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers.relationshipData',
            'assistanceType',
            'assistanceSubtype',
            'documents'
        ])->findOrFail($id);

        // ✅ ADD THESE
        $relationships = Relationship::all();
        $assistanceTypes = AssistanceType::all();
        $assistanceSubtypes = AssistanceSubtype::all();

        return view('social-worker.assess', compact(
            'application',
            'relationships',
            'assistanceTypes',      // ✅ FIX
            'assistanceSubtypes'    // ✅ ALSO THIS (for next dropdown)
        ));
    }
    
}