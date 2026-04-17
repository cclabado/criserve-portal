<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Relationship;
use App\Models\AssistanceType;
use App\Models\AssistanceSubtype;
use Illuminate\Support\Facades\DB;
use App\Models\FamilyMember;
use App\Models\Document;

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
        $application = \App\Models\Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'documents',
            'assistanceType',
            'assistanceSubtype'
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

    public function updateAssessment(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $application = Application::findOrFail($id);

            /*
            |-----------------------------
            | CLIENT UPDATE
            |-----------------------------
            */
            $application->client->update([
                'first_name' => $request->client_first_name,
                'last_name' => $request->client_last_name,
                'middle_name' => $request->client_middle_name,
                'extension_name' => $request->client_extension_name,
                'contact_number' => $request->client_contact_number,
                'full_address' => $request->client_address,
                'sex' => $request->client_sex,
                'birthdate' => $request->client_birthdate,
                'civil_status' => $request->client_civil_status,
            ]);

            /*
            |-----------------------------
            | BENEFICIARY UPDATE
            |-----------------------------
            */
            if ($application->beneficiary) {
                $application->beneficiary->update([
                    'first_name' => $request->beneficiary_first_name,
                    'last_name' => $request->beneficiary_last_name,
                    'middle_name' => $request->beneficiary_middle_name,
                    'extension_name' => $request->beneficiary_extension_name,
                    'contact_number' => $request->beneficiary_contact_number,
                    'full_address' => $request->beneficiary_address,
                    'sex' => $request->beneficiary_sex,
                    'birthdate' => $request->beneficiary_birthdate,
                ]);
            }

            /*
            |-----------------------------
            | FAMILY (UPDATE / INSERT / DELETE)
            |-----------------------------
            */
            $existingIds = $application->familyMembers->pluck('id')->toArray();
            $submittedIds = [];

            if ($request->family) {

                foreach ($request->family as $fam) {

                    if (isset($fam['id'])) {

                        // UPDATE
                        $member = FamilyMember::find($fam['id']);
                        $member->update($fam);

                        $submittedIds[] = $fam['id'];

                    } else {

                        // INSERT
                        $new = FamilyMember::create([
                            'application_id' => $application->id,
                            'first_name' => $fam['first_name'],
                            'last_name' => $fam['last_name'],
                            'middle_name' => $fam['middle_name'],
                            'extension_name' => $fam['extension_name'] ?? null,
                            'relationship' => $fam['relationship'],
                            'birthdate' => $fam['birthdate'],
                        ]);

                        $submittedIds[] = $new->id;
                    }
                }

                // DELETE removed rows
                $toDelete = array_diff($existingIds, $submittedIds);
                FamilyMember::whereIn('id', $toDelete)->delete();
            }

            /*
            |-----------------------------
            | ASSISTANCE UPDATE
            |-----------------------------
            */
            $application->update([
                'assistance_type_id' => $request->assistance_type_id,
                'assistance_subtype_id' => $request->assistance_subtype_id,
                'mode_of_assistance' => $request->mode_of_assistance,
            ]);

            /*
            |-----------------------------
            | DOCUMENT REMARKS
            |-----------------------------
            */
            if ($request->remarks) {
                foreach ($request->remarks as $docId => $remark) {
                    Document::where('id', $docId)->update([
                        'remarks' => $remark
                    ]);
                }
            }

            /*
            |-----------------------------
            | NOTES & SCHEDULE
            |-----------------------------
            */
            $application->update([
                'notes' => $request->notes,
                'schedule_date' => $request->schedule_date,
                'meeting_link' => $request->meeting_link,
                'status' => 'under_review' // auto update status
            ]);

            DB::commit();

            return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Assessment saved successfully.');

        } catch (\Exception $e) {

            DB::rollback();

            return back()->with('error', $e->getMessage());
        }
    }
    public function intake($id)
    {
        $application = \App\Models\Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'assistanceType'
        ])->findOrFail($id);

        return view('social-worker.intake', compact('application'));
    }

    public function saveIntake(Request $request, $id)
    {
        $application = \App\Models\Application::findOrFail($id);

        // Save Inputs
        $application->monthly_income = $request->monthly_income;
        $application->household_members = $request->household_members;
        $application->working_members = $request->working_members;
        $application->monthly_expenses = $request->monthly_expenses;
        $application->savings = $request->savings;

        $application->crisis_type = $request->crisis_type;
        $application->urgency_level = $request->urgency_level;

        $application->has_elderly = $request->has_elderly ? 1 : 0;
        $application->has_child = $request->has_child ? 1 : 0;
        $application->has_pwd = $request->has_pwd ? 1 : 0;
        $application->has_pregnant = $request->has_pregnant ? 1 : 0;
        $application->earner_unable_to_work = $request->earner_unable_to_work ? 1 : 0;

        $application->has_philhealth = $request->has_philhealth ? 1 : 0;
        $application->has_family_support = $request->has_family_support ? 1 : 0;

        // =============================
        // RECOMMENDATION LOGIC
        // =============================
        $score = 0;

        if ($request->monthly_income < 10000) $score += 3;
        elseif ($request->monthly_income < 20000) $score += 2;

        if ($request->monthly_expenses > $request->monthly_income) $score += 2;

        if ($request->savings <= 0) $score += 1;

        if ($request->urgency_level == 'Critical') $score += 4;
        elseif ($request->urgency_level == 'High') $score += 3;
        elseif ($request->urgency_level == 'Medium') $score += 2;

        if (in_array($request->crisis_type, ['Hospitalization', 'Death', 'Disaster'])) {
            $score += 3;
        }

        if ($request->has_elderly) $score += 1;
        if ($request->has_child) $score += 1;
        if ($request->has_pwd) $score += 1;
        if ($request->has_pregnant) $score += 1;
        if ($request->earner_unable_to_work) $score += 2;

        if (!$request->has_family_support) $score += 2;

        // Amount Mapping
        if ($score <= 3) $recommended = 3000;
        elseif ($score <= 6) $recommended = 5000;
        elseif ($score <= 9) $recommended = 8000;
        elseif ($score <= 12) $recommended = 10000;
        else $recommended = 15000;

        $application->problem_statement = $request->problem_statement;
        $application->social_worker_assessment = $request->social_worker_assessment;

        $application->recommended_amount = $recommended;
        $application->final_amount = $request->final_amount;

        $application->status = 'for_approval';

        $application->save();

        return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Intake completed.');
    }
    public function certificate($id)
    {
        $application = \App\Models\Application::with([
            'client',
            'assistanceType'
        ])->findOrFail($id);

        if ($application->status !== 'approved') {
            abort(403, 'Certificate available only for approved applications.');
        }

        return view('social-worker.certificate', compact('application'));
    }
    public function release($id)
    {
        $application = \App\Models\Application::findOrFail($id);

        if ($application->status !== 'approved') {
            return back()->with('error', 'Only approved applications can be released.');
        }

        $application->status = 'released';
        $application->save();

        return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Application marked as released successfully.');
    }
}