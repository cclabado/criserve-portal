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
use App\Services\AiRecommendationService;

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
        $myHandled = \App\Models\Application::where('social_worker_id', auth()->id())->count();

        return view('social-worker.dashboard', compact(
            'totalPending',
            'approvedToday',
            'urgent',
            'totalHandled',
            'myHandled'
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

    public function myCases(Request $request)
    {
        $query = Application::with(['client', 'assistanceType'])
            ->where('social_worker_id', auth()->id())
            ->latest();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%$search%")
                    ->orWhereHas('client', function ($c) use ($search) {
                        $c->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
                    });
            });
        }

        $applications = $query->paginate(8)->withQueryString();

        return view('social-worker.my-cases', compact('applications'));
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
                'social_worker_id' => auth()->id(),
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
            'assistanceType',
            'assistanceSubtype',
            'documents',
        ])->findOrFail($id);

        return view('social-worker.intake', compact('application'));
    }

    public function saveIntake(Request $request, $id)
    {
        $validated = $this->validateIntake($request);
        $application = \App\Models\Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'assistanceType',
            'assistanceSubtype',
        ])->findOrFail($id);

        $recommendation = app(AiRecommendationService::class)
            ->generate($application, $validated);

        $application->fill($this->extractIntakeFields($validated));
        $application->problem_statement = $validated['problem_statement'] ?? null;
        $application->social_worker_assessment = $validated['social_worker_assessment'] ?? null;
        $application->recommended_amount = $recommendation['recommended_amount'];
        $application->final_amount = $validated['final_amount'] ?? $recommendation['recommended_amount'];
        $application->ai_recommendation_summary = $recommendation['summary'];
        $application->ai_recommendation_confidence = $recommendation['confidence'];
        $application->ai_recommendation_source = $recommendation['source'];
        $application->ai_recommendation_model = $recommendation['model'];
        $application->ai_recommendation_generated_at = $recommendation['generated_at'];
        $application->social_worker_id = auth()->id();
        $application->status = 'for_approval';
        $application->save();

        return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Intake completed.');
    }

    public function generateRecommendation(Request $request, $id)
    {
        $validated = $this->validateIntake($request, false);

        $application = \App\Models\Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'assistanceType',
            'assistanceSubtype',
        ])->findOrFail($id);

        return response()->json(
            app(AiRecommendationService::class)->generate($application, $validated)
        );
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

    protected function validateIntake(Request $request, bool $includeNotes = true): array
    {
        $rules = [
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'household_members' => ['required', 'integer', 'min:1'],
            'working_members' => ['required', 'integer', 'min:0'],
            'monthly_expenses' => ['required', 'numeric', 'min:0'],
            'savings' => ['required', 'numeric', 'min:0'],
            'crisis_type' => ['required', 'string', 'max:255'],
            'urgency_level' => ['required', 'in:Low,Medium,High,Critical'],
            'has_elderly' => ['nullable', 'boolean'],
            'has_child' => ['nullable', 'boolean'],
            'has_pwd' => ['nullable', 'boolean'],
            'has_pregnant' => ['nullable', 'boolean'],
            'earner_unable_to_work' => ['nullable', 'boolean'],
            'has_philhealth' => ['nullable', 'boolean'],
            'has_family_support' => ['nullable', 'boolean'],
            'final_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($includeNotes) {
            $rules['problem_statement'] = ['nullable', 'string'];
            $rules['social_worker_assessment'] = ['nullable', 'string'];
        }

        return $request->validate($rules);
    }

    protected function extractIntakeFields(array $validated): array
    {
        return [
            'monthly_income' => $validated['monthly_income'],
            'household_members' => $validated['household_members'],
            'working_members' => $validated['working_members'],
            'monthly_expenses' => $validated['monthly_expenses'],
            'savings' => $validated['savings'],
            'crisis_type' => $validated['crisis_type'],
            'urgency_level' => $validated['urgency_level'],
            'has_elderly' => ! empty($validated['has_elderly']),
            'has_child' => ! empty($validated['has_child']),
            'has_pwd' => ! empty($validated['has_pwd']),
            'has_pregnant' => ! empty($validated['has_pregnant']),
            'earner_unable_to_work' => ! empty($validated['earner_unable_to_work']),
            'has_philhealth' => ! empty($validated['has_philhealth']),
            'has_family_support' => ! empty($validated['has_family_support']),
        ];
    }
}
