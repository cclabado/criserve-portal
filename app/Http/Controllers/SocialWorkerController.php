<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\BeneficiaryProfile;
use App\Models\Document;
use App\Models\FamilyMember;
use App\Models\ModeOfAssistance;
use App\Models\Relationship;
use App\Models\ReferralInstitution;
use App\Notifications\InitialAssessmentScheduledNotification;
use App\Services\AiRecommendationService;
use App\Services\FamilyNetworkService;
use App\Services\FrequencyEligibilityService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialWorkerController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $googleCalendar,
        protected FrequencyEligibilityService $frequencyEligibility,
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    public function dashboard()
    {
        $totalPending = Application::where('status', 'submitted')->count();
        $approvedToday = Application::where('status', 'approved')
            ->whereDate('updated_at', today())
            ->count();
        $urgent = Application::where('status', 'under_review')->count();
        $totalHandled = Application::count();
        $myHandled = Application::where('social_worker_id', auth()->id())->count();
        $releasedThisMonth = Application::where('status', 'released')
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->count();

        $trendDates = collect(range(6, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->startOfDay());
        $trendDates->push(now()->startOfDay());

        $dailyIntakes = $trendDates->map(function (Carbon $date) {
            return Application::whereDate('created_at', $date)->count();
        });

        $trendLabels = $trendDates->map(fn (Carbon $date) => $date->format('M d'));
        $maxDailyIntake = max($dailyIntakes->max() ?: 0, 1);

        $statusBreakdown = [
            'Submitted' => Application::where('status', 'submitted')->count(),
            'Under Review' => Application::where('status', 'under_review')->count(),
            'For Approval' => Application::where('status', 'for_approval')->count(),
            'Approved' => Application::where('status', 'approved')->count(),
            'Released' => Application::where('status', 'released')->count(),
            'Cancelled' => Application::where('status', 'cancelled')->count(),
        ];

        $recentApplications = Application::with(['client', 'assistanceType'])
            ->latest()
            ->take(5)
            ->get();

        return view('social-worker.dashboard', compact(
            'totalPending',
            'approvedToday',
            'urgent',
            'totalHandled',
            'myHandled',
            'releasedThisMonth',
            'trendLabels',
            'dailyIntakes',
            'maxDailyIntake',
            'statusBreakdown',
            'recentApplications'
        ));
    }

    public function applications(Request $request)
    {
        $query = Application::with(['client', 'assistanceType', 'modeOfAssistance'])->latest();

        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->type && $request->type != 'all') {
            $query->where('assistance_type_id', $request->type);
        }

        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%$search%");
                $q->orWhereHas('client', function ($c) use ($search) {
                    $c->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
                });
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->paginate(5)->withQueryString();

        return view('social-worker.applications', compact('applications'));
    }

    public function myCases(Request $request)
    {
        $query = Application::with(['client', 'assistanceType', 'modeOfAssistance'])
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

    public function schedule(Request $request)
    {
        $activeScheduleStatuses = ['submitted', 'under_review'];

        $query = Application::with([
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
            ])
            ->where('social_worker_id', auth()->id())
            ->whereIn('status', $activeScheduleStatuses)
            ->whereNotNull('schedule_date')
            ->orderBy('schedule_date');

        if ($request->filled('schedule_scope')) {
            if ($request->schedule_scope === 'upcoming') {
                $query->where('schedule_date', '>=', now());
            }

            if ($request->schedule_scope === 'past') {
                $query->where('schedule_date', '<', now());
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('schedule_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('schedule_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%$search%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
                    })
                    ->orWhereHas('beneficiary', function ($beneficiaryQuery) use ($search) {
                        $beneficiaryQuery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
                    });
            });
        }

        $schedules = $query->paginate(10)->withQueryString();
        $totalScheduled = Application::where('social_worker_id', auth()->id())
            ->whereIn('status', $activeScheduleStatuses)
            ->whereNotNull('schedule_date')
            ->count();
        $upcomingCount = Application::where('social_worker_id', auth()->id())
            ->whereIn('status', $activeScheduleStatuses)
            ->whereNotNull('schedule_date')
            ->where('schedule_date', '>=', now())
            ->count();
        $todayCount = Application::where('social_worker_id', auth()->id())
            ->whereIn('status', $activeScheduleStatuses)
            ->whereNotNull('schedule_date')
            ->whereDate('schedule_date', today())
            ->count();
        $googleConnected = $request->user()->hasGoogleCalendarConnection();

        return view('social-worker.schedule', compact(
            'schedules',
            'totalScheduled',
            'upcomingCount',
            'todayCount',
            'googleConnected'
        ));
    }

    public function show($id)
    {
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'beneficiaryProfile',
            'documents',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'frequencyRule',
            'frequencyBasisApplication',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'assistanceRecommendations.referralInstitution',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        $householdMembers = $this->resolveHouseholdMembers($application);
        $familyNetwork = $this->familyNetwork->buildApplicationNetwork($application);

        return view('social-worker.show', compact('application', 'householdMembers', 'familyNetwork'));
    }

    public function assess($id)
    {
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'beneficiaryProfile',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'frequencyRule',
            'frequencyBasisApplication.client',
            'frequencyBasisApplication.assistanceType',
            'frequencyBasisApplication.assistanceSubtype',
            'frequencyBasisApplication.assistanceDetail',
            'frequencyBasisApplication.modeOfAssistance',
            'documents',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        $beneficiaryProfileId = $application->beneficiary_profile_id;
        $frequencyPreview = null;

        if ($application->assistance_subtype_id) {
            $frequencyPreview = $this->frequencyEligibility->evaluate([
                'client_id' => $application->client_id,
                'beneficiary_profile_id' => $beneficiaryProfileId,
                'frequency_subject' => $beneficiaryProfileId ? 'beneficiary' : 'client',
                'assistance_subtype_id' => (int) $application->assistance_subtype_id,
                'assistance_detail_id' => $application->assistance_detail_id ? (int) $application->assistance_detail_id : null,
                'frequency_case_key' => $application->frequency_case_key,
                'frequency_override_reason' => $application->frequency_override_reason,
            ], $application);

            if (! empty($frequencyPreview['basis_application_id']) && ! $application->frequencyBasisApplication) {
                $application->setRelation(
                    'frequencyBasisApplication',
                    Application::with(['client', 'assistanceType', 'assistanceSubtype', 'assistanceDetail', 'modeOfAssistance'])
                        ->find($frequencyPreview['basis_application_id'])
                );
            }
        }

        $relationships = Relationship::where('is_active', true)->orderBy('name')->get();
        $assistanceTypes = AssistanceType::where('is_active', true)->orderBy('name')->get();
        $assistanceSubtypes = AssistanceSubtype::with([
                'frequencyRule',
                'details' => fn ($query) => $query->where('is_active', true)->with('frequencyRule'),
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $modesOfAssistance = ModeOfAssistance::where('is_active', true)->orderBy('name')->get();
        $householdMembers = $this->resolveHouseholdMembers($application);

        return view('social-worker.assess', compact(
            'application',
            'frequencyPreview',
            'householdMembers',
            'relationships',
            'assistanceTypes',
            'assistanceSubtypes',
            'modesOfAssistance'
        ));
    }

    public function updateAssessment(Request $request, $id)
    {
        $request->validate([
            'schedule_date' => ['nullable', 'date'],
            'meeting_link' => ['nullable', 'string', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'mode_of_assistance_id' => ['required', 'exists:mode_of_assistances,id'],
            'frequency_case_key' => ['nullable', 'string', 'max:255'],
            'frequency_override_reason' => ['nullable', 'string'],
            'assessment_action' => ['nullable', 'in:save,cancel_due_to_frequency'],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        $this->validateAssistanceSelection(
            (int) $request->assistance_type_id,
            (int) $request->assistance_subtype_id,
            $request->filled('assistance_detail_id') ? (int) $request->assistance_detail_id : null
        );
        $mode = ModeOfAssistance::findOrFail((int) $request->mode_of_assistance_id);

        DB::beginTransaction();

        try {
            $application = Application::with([
                'client',
                'socialWorker',
                'beneficiary.relationshipData',
                'beneficiaryProfile',
                'documents',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'modeOfAssistance',
            ])->findOrFail($id);
            $this->claimOrEnsureOwnership($application);

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

            if ($application->beneficiary) {
                $application->beneficiary->update([
                    'beneficiary_profile_id' => $application->beneficiary_profile_id,
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

            if ($application->beneficiary && $application->beneficiary->relationship_id != 1) {
                $beneficiaryProfile = $application->beneficiaryProfile
                    ?? BeneficiaryProfile::firstOrNew([
                        'client_id' => $application->client_id,
                        'last_name' => $request->beneficiary_last_name,
                        'first_name' => $request->beneficiary_first_name,
                        'middle_name' => $request->beneficiary_middle_name,
                        'extension_name' => $request->beneficiary_extension_name,
                        'birthdate' => $request->beneficiary_birthdate,
                    ]);

                $beneficiaryProfile->fill([
                    'relationship_id' => $application->beneficiary->relationship_id,
                    'sex' => $request->beneficiary_sex,
                    'contact_number' => $request->beneficiary_contact_number,
                    'full_address' => $request->beneficiary_address,
                ]);
                $beneficiaryProfile->save();
                $this->familyNetwork->syncBeneficiaryProfile($beneficiaryProfile);

                if ($application->beneficiary_profile_id !== $beneficiaryProfile->id) {
                    $application->beneficiary_profile_id = $beneficiaryProfile->id;
                    $application->save();
                }

                $application->beneficiary->update([
                    'beneficiary_profile_id' => $beneficiaryProfile->id,
                ]);
            }

            $beneficiaryProfileId = $application->beneficiary_profile_id;
            $familyQuery = $beneficiaryProfileId
                ? FamilyMember::where('beneficiary_profile_id', $beneficiaryProfileId)
                : FamilyMember::where('client_id', $application->client_id)->whereNull('beneficiary_profile_id');

            $frequencyEvaluation = $this->frequencyEligibility->evaluate([
                'client_id' => $application->client_id,
                'beneficiary_profile_id' => $beneficiaryProfileId,
                'frequency_subject' => $beneficiaryProfileId ? 'beneficiary' : 'client',
                'assistance_subtype_id' => (int) $request->assistance_subtype_id,
                'assistance_detail_id' => $request->filled('assistance_detail_id') ? (int) $request->assistance_detail_id : null,
                'frequency_case_key' => $request->frequency_case_key,
                'frequency_override_reason' => $request->frequency_override_reason,
            ], $application);

            $frequencyStatus = $frequencyEvaluation['status'];
            $frequencyMessage = $frequencyEvaluation['message'];

            if ($frequencyEvaluation['status'] === 'blocked' && blank($request->frequency_override_reason)) {
                $frequencyStatus = 'blocked';
                $frequencyMessage = $frequencyEvaluation['message'];
            } elseif ($frequencyEvaluation['status'] === 'blocked' && filled($request->frequency_override_reason)) {
                $frequencyStatus = 'overridden';
                $frequencyMessage = 'Frequency rule overridden by social worker. '.$frequencyEvaluation['message'];
            }

            $existingIds = $familyQuery->pluck('id')->toArray();
            $submittedIds = [];

            if ($request->family) {
                foreach ($request->family as $fam) {
                    $payload = [
                        'application_id' => $application->id,
                        'client_id' => $application->client_id,
                        'beneficiary_profile_id' => $beneficiaryProfileId,
                        'first_name' => $fam['first_name'],
                        'last_name' => $fam['last_name'],
                        'middle_name' => $fam['middle_name'] ?? null,
                        'extension_name' => $fam['extension_name'] ?? null,
                        'relationship' => $fam['relationship'],
                        'birthdate' => $fam['birthdate'],
                    ];

                    if (isset($fam['id'])) {
                        $member = (clone $familyQuery)->find($fam['id']);
                        if ($member) {
                            $member->update($payload);
                            $this->familyNetwork->syncFamilyMember($member);
                            $submittedIds[] = $member->id;
                        }
                    } else {
                        $new = FamilyMember::create($payload);
                        $this->familyNetwork->syncFamilyMember($new);
                        $submittedIds[] = $new->id;
                    }
                }

                $toDelete = array_diff($existingIds, $submittedIds);
                if (! empty($toDelete)) {
                    (clone $familyQuery)->whereIn('id', $toDelete)->delete();
                }
            }

            $application->update([
                'assistance_type_id' => $request->assistance_type_id,
                'assistance_subtype_id' => $request->assistance_subtype_id,
                'assistance_detail_id' => $request->assistance_detail_id,
                'mode_of_assistance_id' => $mode->id,
                'frequency_rule_id' => $frequencyEvaluation['rule']?->id,
                'frequency_basis_application_id' => $frequencyEvaluation['basis_application_id'],
                'frequency_status' => $frequencyStatus,
                'frequency_message' => $frequencyMessage,
                'frequency_reference_date' => null,
                'frequency_case_key' => $request->frequency_case_key,
                'frequency_exception_reason' => null,
                'frequency_override_reason' => $request->frequency_override_reason,
                'frequency_checked_at' => now(),
                'mode_of_assistance' => $mode->name,
                'social_worker_id' => auth()->id(),
                'denial_reason' => null,
            ]);
            $this->familyNetwork->syncClient($application->client);
            if ($application->beneficiary) {
                $this->familyNetwork->syncBeneficiary($application->beneficiary->fresh());
            }
            $this->familyNetwork->syncApplicationNetwork($application->fresh([
                'client.user',
                'beneficiary',
                'beneficiaryProfile',
                'familyMembers',
            ]));

            if ($request->assessment_action === 'cancel_due_to_frequency') {
                $application->status = 'cancelled';
                $application->denial_reason = $this->buildFrequencyCancellationReason(
                    $application->frequencyBasisApplication,
                    $request->cancellation_reason
                );
                $application->save();

                DB::commit();

                return redirect()
                    ->route('socialworker.applications')
                    ->with('success', 'Application cancelled based on previous released assistance history.');
            }

            if ($frequencyEvaluation['status'] === 'blocked' && blank($request->frequency_override_reason)) {
                $application->status = 'cancelled';
                $application->denial_reason = $this->buildFrequencyCancellationReason(
                    $application->frequencyBasisApplication,
                    $frequencyEvaluation['message']
                );
                $application->save();

                DB::commit();

                return redirect()
                    ->route('socialworker.applications')
                    ->with('success', 'Application cancelled because it is ineligible under the frequency of assistance rule.');
            }

            if ($request->remarks) {
                foreach ($request->remarks as $docId => $remark) {
                    Document::where('id', $docId)->update([
                        'remarks' => $remark,
                    ]);
                }
            }

            $scheduleData = [
                'notes' => $request->notes,
                'schedule_date' => $request->schedule_date,
                'meeting_link' => $request->meeting_link,
                'status' => 'under_review',
            ];

            $googleEvent = $this->googleCalendar->syncAssessmentSchedule(
                $request->user(),
                $application,
                $request->schedule_date,
                $request->notes
            );

            if (is_array($googleEvent)) {
                $scheduleData['meeting_link'] = $googleEvent['meeting_link'] ?? null;
                $scheduleData['google_calendar_event_id'] = $googleEvent['google_calendar_event_id'] ?? null;
                $scheduleData['google_calendar_event_link'] = $googleEvent['google_calendar_event_link'] ?? null;
            }

            $application->update($scheduleData);

            DB::commit();

            $application->refresh();
            $application->loadMissing('client');

            $clientUser = $application->client?->user;

            if ($clientUser) {
                $clientUser->notify(new InitialAssessmentScheduledNotification($application));
            }

            return redirect()
                ->route('socialworker.applications')
                ->with('success', 'Assessment saved successfully.')
                ->with('frequency_warning', in_array($frequencyStatus, ['review_required', 'overridden'], true) ? $frequencyMessage : null);
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (HttpResponseException $e) {
            DB::rollback();
            throw $e;
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', $e->getMessage());
        }
    }

    public function intake($id)
    {
        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'assistanceRecommendations.frequencyRule',
            'assistanceRecommendations.referralInstitution',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'frequencyRule',
            'frequencyBasisApplication',
            'documents',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        $assistanceTypes = AssistanceType::with([
                'subtypes' => fn ($query) => $query->where('is_active', true)->with([
                    'frequencyRule',
                    'details' => fn ($detailQuery) => $detailQuery->where('is_active', true)->with('frequencyRule'),
                ]),
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $modesOfAssistance = ModeOfAssistance::where('is_active', true)->orderBy('name')->get();
        $referralInstitutions = ReferralInstitution::where('is_active', true)->orderBy('name')->get();

        return view('social-worker.intake', compact(
            'application',
            'assistanceTypes',
            'modesOfAssistance',
            'referralInstitutions'
        ));
    }

    public function saveIntake(Request $request, $id)
    {
        $validated = $this->validateIntake($request);
        $application = Application::with([
            'client',
            'socialWorker',
            'beneficiary',
            'familyMembers',
            'assistanceRecommendations',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'frequencyRule',
            'frequencyBasisApplication',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        $recommendations = $this->validateAdditionalRecommendations($request, $application);

        $recommendation = app(AiRecommendationService::class)
            ->generate($application, $validated);

        $application->fill($this->extractIntakeFields($validated));
        $application->problem_statement = $validated['problem_statement'] ?? null;
        $application->social_worker_assessment = $validated['social_worker_assessment'] ?? null;
        $application->recommended_amount = $recommendation['recommended_amount'];
        $application->final_amount = collect($recommendations)->sum(fn (array $row) => (float) ($row['final_amount'] ?? 0));
        $application->ai_recommendation_summary = $recommendation['summary'];
        $application->ai_recommendation_confidence = $recommendation['confidence'];
        $application->ai_recommendation_source = $recommendation['source'];
        $application->ai_recommendation_model = $recommendation['model'];
        $application->ai_recommendation_generated_at = $recommendation['generated_at'];
        $application->social_worker_id = auth()->id();
        $application->status = 'for_approval';

        DB::transaction(function () use ($application, $recommendations) {
            $application->save();
            $this->syncAssistanceRecommendations($application, $recommendations);
        });

        return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Intake completed.');
    }

    public function generateRecommendation(Request $request, $id)
    {
        $validated = $this->validateIntake($request, false);

        $application = Application::with([
            'client',
            'beneficiary',
            'familyMembers',
            'assistanceType',
            'assistanceSubtype',
            'socialWorker',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        return response()->json(
            app(AiRecommendationService::class)->generate($application, $validated)
        );
    }

    public function checkAdditionalAssistanceFrequency(Request $request, $id)
    {
        $application = Application::with(['client', 'beneficiaryProfile'])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        $validated = $request->validate([
            'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'frequency_case_key' => ['nullable', 'string', 'max:255'],
            'frequency_override_reason' => ['nullable', 'string'],
        ]);

        $this->validateAssistanceSelection(
            (int) $validated['assistance_type_id'],
            (int) $validated['assistance_subtype_id'],
            isset($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null
        );

        return response()->json($this->evaluateAdditionalRecommendationFrequency($application, $validated));
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
            'frequencyRule',
            'frequencyBasisApplication',
            'documents',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.referralInstitution',
            'socialWorker',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        if (! in_array($application->status, ['approved', 'released'], true)) {
            abort(403, 'Certificate available only for approved or released applications.');
        }

        return view('social-worker.certificate', compact('application'));
    }

    public function generalIntakeSheet($id)
    {
        $application = Application::with([
            'client',
            'beneficiary.relationshipData',
            'familyMembers.relationshipData',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'modeOfAssistance',
            'documents',
            'assistanceRecommendations.assistanceType',
            'assistanceRecommendations.assistanceSubtype',
            'assistanceRecommendations.assistanceDetail',
            'assistanceRecommendations.modeOfAssistance',
            'assistanceRecommendations.referralInstitution',
            'socialWorker',
        ])->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        return view('social-worker.general-intake-sheet', compact('application'));
    }

    public function release($id)
    {
        $application = Application::with('socialWorker')->findOrFail($id);
        $this->claimOrEnsureOwnership($application);

        if ($application->status !== 'approved') {
            return back()->with('error', 'Only approved applications can be released.');
        }

        $application->status = 'released';
        $application->save();

        return redirect()
            ->route('socialworker.applications')
            ->with('success', 'Application marked as released successfully.');
    }

    protected function buildFrequencyCancellationReason(?Application $basisApplication, ?string $customReason = null): string
    {
        $customReason = trim((string) $customReason);

        if ($customReason !== '') {
            return $customReason;
        }

        if (! $basisApplication) {
            return 'Application cancelled due to previous assistance availment under the frequency of assistance policy.';
        }

        $basisDate = $basisApplication->updated_at?->format('M d, Y') ?? $basisApplication->created_at?->format('M d, Y') ?? 'an earlier release date';

        return sprintf(
            'Previously availed assistance on %s under application reference no. %s.',
            $basisDate,
            $basisApplication->reference_no
        );
    }

    protected function claimOrEnsureOwnership(Application $application): void
    {
        $currentUserId = auth()->id();

        if (! $currentUserId) {
            abort(403, 'You must be logged in to access this application.');
        }

        if ((int) $application->social_worker_id === (int) $currentUserId) {
            return;
        }

        if (is_null($application->social_worker_id)) {
            $claimed = Application::whereKey($application->id)
                ->whereNull('social_worker_id')
                ->update(['social_worker_id' => $currentUserId]);

            $application->refresh();

            if ($claimed === 1 && (int) $application->social_worker_id === (int) $currentUserId) {
                return;
            }
        }

        $application->loadMissing('socialWorker');

        $ownerName = $application->socialWorker?->name
            ?? $application->socialWorker?->email
            ?? 'another social worker';

        throw new HttpResponseException(
            redirect()
                ->route('socialworker.applications')
                ->with('error', 'This application is already being handled by '.$ownerName.'.')
        );
    }

    protected function validateIntake(Request $request, bool $includeNotes = true): array
    {
        $rules = [
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'household_members' => ['nullable', 'integer', 'min:1'],
            'working_members' => ['required', 'integer', 'min:0'],
            'seasonal_worker_members' => ['required', 'integer', 'min:0'],
            'monthly_expenses' => ['required', 'numeric', 'min:0'],
            'savings' => ['nullable', 'numeric', 'min:0'],
            'crisis_type' => ['nullable', 'string', 'max:255'],
            'urgency_level' => ['nullable', 'in:Low,Medium,High,Critical'],
            'has_elderly' => ['nullable', 'boolean'],
            'has_child' => ['nullable', 'boolean'],
            'has_pwd' => ['nullable', 'boolean'],
            'has_pregnant' => ['nullable', 'boolean'],
            'earner_unable_to_work' => ['nullable', 'boolean'],
            'has_philhealth' => ['nullable', 'boolean'],
            'has_family_support' => ['nullable', 'boolean'],
            'has_vulnerable_household_member' => ['nullable', 'boolean'],
            'has_unstable_employment' => ['nullable', 'boolean'],
            'has_insurance_coverage' => ['nullable', 'boolean'],
            'has_savings' => ['nullable', 'boolean'],
            'amount_needed' => ['required', 'numeric', 'min:0'],
            'gis_client_type' => ['nullable', 'in:New,Returning,Referral'],
            'gis_visit_type' => ['nullable', 'string', 'max:255'],
            'diagnosis_or_cause_of_death' => ['nullable', 'string', 'max:255'],
            'occupation_sources' => ['nullable', 'string', 'max:255'],
            'insurance_coverage' => ['nullable', 'string', 'max:255'],
            'emergency_fund' => ['nullable', 'string', 'max:255'],
            'disease_duration' => ['nullable', 'string', 'max:255'],
            'experienced_recent_crisis' => ['nullable', 'boolean'],
            'recent_crisis_types' => ['nullable', 'array'],
            'recent_crisis_types.*' => ['string', 'max:255'],
            'support_systems' => ['nullable', 'array'],
            'support_systems.*' => ['string', 'max:255'],
            'external_resources' => ['nullable', 'array'],
            'external_resources.*' => ['string', 'max:255'],
            'self_help_efforts' => ['nullable', 'array'],
            'self_help_efforts.*' => ['string', 'max:255'],
            'client_sector' => ['nullable', 'string', 'max:255'],
            'client_sectors' => ['nullable', 'array'],
            'client_sectors.*' => ['string', 'max:255'],
            'client_sub_category' => ['nullable', 'string', 'max:255'],
            'client_sub_categories' => ['nullable', 'array'],
            'client_sub_categories.*' => ['string', 'max:255'],
            'disability_type' => ['nullable', 'string', 'max:255'],
            'disability_types' => ['nullable', 'array'],
            'disability_types.*' => ['string', 'max:255'],
            'total_income_past_six_months' => ['nullable', 'numeric', 'min:0'],
            'income_sources' => ['nullable', 'array'],
            'income_sources.*.source' => ['nullable', 'string', 'max:255'],
            'income_sources.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($includeNotes) {
            $rules['problem_statement'] = ['nullable', 'string'];
            $rules['social_worker_assessment'] = ['nullable', 'string'];
        }

        return $request->validate($rules);
    }

    protected function validateAdditionalRecommendations(Request $request, Application $application): array
    {
        $validated = $request->validate([
            'recommendations' => ['nullable', 'array'],
            'recommendations.*.assistance_type_id' => ['nullable', 'exists:assistance_types,id'],
            'recommendations.*.assistance_subtype_id' => ['nullable', 'exists:assistance_subtypes,id'],
            'recommendations.*.assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'recommendations.*.mode_of_assistance_id' => ['nullable', 'exists:mode_of_assistances,id'],
            'recommendations.*.referral_institution_id' => ['nullable', 'exists:referral_institutions,id'],
            'recommendations.*.recommended_amount' => ['nullable', 'numeric', 'min:0'],
            'recommendations.*.final_amount' => ['nullable', 'numeric', 'min:0'],
            'recommendations.*.frequency_case_key' => ['nullable', 'string', 'max:255'],
            'recommendations.*.frequency_override_reason' => ['nullable', 'string'],
            'recommendations.*.notes' => ['nullable', 'string'],
        ]);

        $rows = collect($validated['recommendations'] ?? [])
            ->filter(fn (array $row) => filled($row['assistance_type_id'] ?? null)
                || filled($row['assistance_subtype_id'] ?? null)
                || filled($row['final_amount'] ?? null)
                || filled($row['referral_institution_id'] ?? null))
            ->values();

        $seen = [];

        return $rows->map(function (array $row, int $index) use ($application, &$seen) {
            $detailId = filled($row['assistance_detail_id'] ?? null) ? (int) $row['assistance_detail_id'] : null;
            $isNonMonetary = $this->isNonMonetaryAssistance(
                (int) ($row['assistance_type_id'] ?? 0),
                (int) ($row['assistance_subtype_id'] ?? 0)
            );
            $isReferralService = $this->isReferralAssistance(
                (int) ($row['assistance_type_id'] ?? 0),
                (int) ($row['assistance_subtype_id'] ?? 0)
            );
            $isPsychosocialService = $this->isPsychosocialAssistance(
                (int) ($row['assistance_type_id'] ?? 0),
                (int) ($row['assistance_subtype_id'] ?? 0)
            );
            $requiresModeOfAssistance = $this->requiresModeOfAssistance(
                (int) ($row['assistance_type_id'] ?? 0),
                (int) ($row['assistance_subtype_id'] ?? 0)
            );

            if ($isPsychosocialService) {
                $detailId = null;
                $row['assistance_detail_id'] = null;
            }

            $rowRules = [
                'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            ];

            if (! $isReferralService) {
                $rowRules['assistance_subtype_id'] = ['required', 'exists:assistance_subtypes,id'];
            }

            if (! $isNonMonetary) {
                $rowRules['final_amount'] = ['required', 'numeric', 'min:0'];
            }

            if ($requiresModeOfAssistance) {
                $rowRules['mode_of_assistance_id'] = ['required', 'exists:mode_of_assistances,id'];
            }

            if ($isReferralService) {
                $rowRules['referral_institution_id'] = ['required', 'exists:referral_institutions,id'];
            }

            validator($row, $rowRules)->validate();

            if (! $isReferralService) {
                $this->validateAssistanceSelection(
                    (int) $row['assistance_type_id'],
                    (int) $row['assistance_subtype_id'],
                    $detailId,
                    ! $isPsychosocialService
                );
            }

            $signature = $this->recommendationSignature([
                'assistance_type_id' => $row['assistance_type_id'],
                'assistance_subtype_id' => $row['assistance_subtype_id'] ?? null,
                'assistance_detail_id' => $detailId,
            ]);

            if (isset($seen[$signature])) {
                throw ValidationException::withMessages([
                    "recommendations.{$index}.assistance_subtype_id" => 'This assistance is already included in the intake recommendation.',
                ]);
            }

            $seen[$signature] = true;

            $frequency = $isNonMonetary
                ? [
                    'rule' => null,
                    'basis_application_id' => null,
                    'status' => 'eligible',
                    'message' => 'Added non-monetary service. No frequency check is required.',
                ]
                : $this->evaluateAdditionalRecommendationFrequency($application, $row);

            $isEligible = ($frequency['status'] ?? null) === 'eligible';

            if (! $isEligible && blank($row['frequency_override_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    "recommendations.{$index}.assistance_subtype_id" => $frequency['message'],
                ]);
            }

            if (! $isEligible) {
                $frequency['status'] = 'overridden';
                $frequency['message'] = 'Frequency rule overridden by social worker. '.$frequency['message'];
            }

            return [
                'assistance_type_id' => (int) $row['assistance_type_id'],
                'assistance_subtype_id' => $isReferralService ? null : (int) $row['assistance_subtype_id'],
                'assistance_detail_id' => $detailId,
                'mode_of_assistance_id' => $requiresModeOfAssistance ? (int) $row['mode_of_assistance_id'] : null,
                'referral_institution_id' => $isReferralService ? (int) $row['referral_institution_id'] : null,
                'recommended_amount' => null,
                'final_amount' => $isNonMonetary ? 0 : $row['final_amount'],
                'frequency_rule_id' => $frequency['rule']?->id,
                'frequency_basis_application_id' => $frequency['basis_application_id'],
                'frequency_status' => $frequency['status'],
                'frequency_message' => $frequency['message'],
                'frequency_case_key' => $row['frequency_case_key'] ?? null,
                'frequency_override_reason' => $row['frequency_override_reason'] ?? null,
                'frequency_checked_at' => now(),
                'notes' => $row['notes'] ?? null,
                'sort_order' => $index,
            ];
        })->all();
    }

    protected function syncAssistanceRecommendations(Application $application, array $recommendations): void
    {
        $application->assistanceRecommendations()->delete();

        foreach ($recommendations as $recommendation) {
            $application->assistanceRecommendations()->create($recommendation);
        }
    }

    protected function evaluateAdditionalRecommendationFrequency(Application $application, array $row): array
    {
        return $this->frequencyEligibility->evaluate([
            'client_id' => $application->client_id,
            'beneficiary_profile_id' => $application->beneficiary_profile_id,
            'frequency_subject' => $application->beneficiary_profile_id ? 'beneficiary' : 'client',
            'assistance_subtype_id' => (int) $row['assistance_subtype_id'],
            'assistance_detail_id' => filled($row['assistance_detail_id'] ?? null) ? (int) $row['assistance_detail_id'] : null,
            'frequency_case_key' => $row['frequency_case_key'] ?? null,
            'frequency_override_reason' => $row['frequency_override_reason'] ?? null,
        ], $application);
    }

    protected function recommendationSignature(array $row): string
    {
        if (blank($row['assistance_subtype_id'] ?? null)) {
            return 'type:'.(int) ($row['assistance_type_id'] ?? 0);
        }

        return implode(':', [
            (int) ($row['assistance_subtype_id'] ?? 0),
            filled($row['assistance_detail_id'] ?? null) ? (int) $row['assistance_detail_id'] : 'none',
        ]);
    }

    protected function isNonMonetaryAssistance(int $typeId, int $subtypeId): bool
    {
        return $this->isPsychosocialAssistance($typeId, $subtypeId)
            || $this->isReferralAssistance($typeId, $subtypeId)
            || $this->isMaterialAssistance($typeId, $subtypeId);
    }

    protected function isPsychosocialAssistance(int $typeId, int $subtypeId): bool
    {
        $typeName = strtolower((string) AssistanceType::whereKey($typeId)->value('name'));
        $subtypeName = strtolower((string) AssistanceSubtype::whereKey($subtypeId)->value('name'));

        return str_contains($typeName, 'psychosocial')
            || str_contains($subtypeName, 'psychosocial');
    }

    protected function isReferralAssistance(int $typeId, int $subtypeId): bool
    {
        $typeName = strtolower((string) AssistanceType::whereKey($typeId)->value('name'));
        $subtypeName = strtolower((string) AssistanceSubtype::whereKey($subtypeId)->value('name'));

        return str_contains($typeName, 'referral')
            || str_contains($subtypeName, 'referral');
    }

    protected function isMaterialAssistance(int $typeId, int $subtypeId): bool
    {
        $typeName = strtolower((string) AssistanceType::whereKey($typeId)->value('name'));
        $subtypeName = strtolower((string) AssistanceSubtype::whereKey($subtypeId)->value('name'));

        return str_contains($typeName, 'material')
            || str_contains($subtypeName, 'material');
    }

    protected function requiresModeOfAssistance(int $typeId, int $subtypeId): bool
    {
        return ! $this->isNonMonetaryAssistance($typeId, $subtypeId);
    }

    protected function extractIntakeFields(array $validated): array
    {
        return [
            'monthly_income' => $validated['monthly_income'],
            'household_members' => $validated['household_members'] ?? null,
            'working_members' => $validated['working_members'],
            'seasonal_worker_members' => $validated['seasonal_worker_members'],
            'monthly_expenses' => $validated['monthly_expenses'],
            'savings' => $validated['savings'] ?? 0,
            'crisis_type' => $validated['crisis_type'] ?? null,
            'urgency_level' => $validated['urgency_level'] ?? null,
            'has_elderly' => ! empty($validated['has_elderly']),
            'has_child' => ! empty($validated['has_child']),
            'has_pwd' => ! empty($validated['has_pwd']),
            'has_pregnant' => ! empty($validated['has_pregnant']),
            'earner_unable_to_work' => ! empty($validated['earner_unable_to_work']),
            'has_philhealth' => ! empty($validated['has_philhealth']),
            'has_family_support' => ! empty($validated['has_family_support']),
            'has_vulnerable_household_member' => ! empty($validated['has_vulnerable_household_member']),
            'has_unstable_employment' => ! empty($validated['has_unstable_employment']),
            'has_insurance_coverage' => ! empty($validated['has_insurance_coverage']),
            'has_savings' => ! empty($validated['has_savings']),
            'amount_needed' => $validated['amount_needed'],
            'gis_client_type' => $validated['gis_client_type'] ?? null,
            'gis_visit_type' => $validated['gis_visit_type'] ?? null,
            'diagnosis_or_cause_of_death' => $validated['diagnosis_or_cause_of_death'] ?? null,
            'occupation_sources' => $validated['occupation_sources'] ?? null,
            'insurance_coverage' => $validated['insurance_coverage'] ?? null,
            'emergency_fund' => $validated['emergency_fund'] ?? null,
            'disease_duration' => $validated['disease_duration'] ?? null,
            'experienced_recent_crisis' => array_key_exists('experienced_recent_crisis', $validated)
                ? (bool) $validated['experienced_recent_crisis']
                : null,
            'recent_crisis_types' => array_values($validated['recent_crisis_types'] ?? []),
            'support_systems' => array_values($validated['support_systems'] ?? []),
            'external_resources' => array_values($validated['external_resources'] ?? []),
            'self_help_efforts' => array_values($validated['self_help_efforts'] ?? []),
            'client_sector' => collect($validated['client_sectors'] ?? [])->first() ?? ($validated['client_sector'] ?? null),
            'client_sectors' => array_values($validated['client_sectors'] ?? []),
            'client_sub_category' => collect($validated['client_sub_categories'] ?? [])->first() ?? ($validated['client_sub_category'] ?? null),
            'client_sub_categories' => array_values($validated['client_sub_categories'] ?? []),
            'disability_type' => in_array('PWD', $validated['client_sectors'] ?? [], true)
                ? (collect($validated['disability_types'] ?? [])->first() ?? ($validated['disability_type'] ?? null))
                : null,
            'disability_types' => in_array('PWD', $validated['client_sectors'] ?? [], true)
                ? array_values($validated['disability_types'] ?? [])
                : [],
            'total_income_past_six_months' => $validated['total_income_past_six_months'] ?? null,
            'income_sources' => collect($validated['income_sources'] ?? [])
                ->filter(fn (array $row) => filled($row['source'] ?? null) || filled($row['amount'] ?? null))
                ->values()
                ->all(),
        ];
    }

    protected function resolveHouseholdMembers(Application $application)
    {
        if ($application->usesBeneficiaryHousehold() && $application->beneficiaryProfile) {
            return $application->beneficiaryProfile
                ->familyMembers()
                ->with('relationshipData')
                ->orderBy('id')
                ->get();
        }

        return $application->client
            ->familyMembers()
            ->whereNull('beneficiary_profile_id')
            ->with('relationshipData')
            ->orderBy('id')
            ->get();
    }

    protected function validateAssistanceSelection(int $typeId, int $subtypeId, ?int $detailId, bool $requireDetail = true): void
    {
        $type = AssistanceType::find($typeId);
        $subtype = AssistanceSubtype::with('details')->find($subtypeId);

        if (! $type || ! $subtype || $subtype->assistance_type_id !== $type->id) {
            throw ValidationException::withMessages([
                'assistance_subtype_id' => 'The selected assistance subtype does not belong to the chosen assistance type.',
            ]);
        }

        if ($requireDetail && $subtype->details->isNotEmpty() && ! $detailId) {
            throw ValidationException::withMessages([
                'assistance_detail_id' => 'Please select an assistance detail.',
            ]);
        }

        if ($detailId) {
            $detail = AssistanceDetail::find($detailId);

            if (! $detail || $detail->assistance_subtype_id !== $subtype->id) {
                throw ValidationException::withMessages([
                    'assistance_detail_id' => 'The selected assistance detail does not belong to the chosen subtype.',
                ]);
            }
        }
    }
}
