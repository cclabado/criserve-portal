<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProfile;
use App\Models\FamilyMember;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use App\Models\ModeOfAssistance;
use App\Services\FamilyNetworkService;
use App\Services\FrequencyEligibilityService;
use App\Services\IdentityMappingService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function __construct(
        protected FrequencyEligibilityService $frequencyEligibility,
        protected IdentityMappingService $identityMapping,
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    public function create()
    {
        $user = auth()->user();
        $client = Client::with([
                'familyMembers' => function ($query) {
                    $query->whereNull('application_id')
                        ->whereNull('beneficiary_profile_id')
                        ->with('relationshipData');
                },
                'beneficiaryProfiles.familyMembers.relationshipData',
            ])
            ->where('user_id', $user->id)
            ->first();

        return view('client.application-form', compact('user', 'client'));
    }

    public function lookupBeneficiaryProfile(Request $request): JsonResponse
    {
        $client = Client::where('user_id', $request->user()->id)->first();

        if (! $client) {
            return response()->json(['profile' => null, 'family' => []]);
        }

        $profile = $client->beneficiaryProfiles()
            ->with('familyMembers.relationshipData')
            ->where('last_name', trim((string) $request->input('last_name')))
            ->where('first_name', trim((string) $request->input('first_name')))
            ->where('middle_name', $request->input('middle_name'))
            ->where('extension_name', $request->input('extension_name'))
            ->where('birthdate', $request->input('birthdate'))
            ->first();

        if (! $profile) {
            return response()->json(['profile' => null, 'family' => []]);
        }

        return response()->json([
            'profile' => [
                'id' => $profile->id,
                'name' => trim(implode(' ', array_filter([
                    $profile->first_name,
                    $profile->middle_name,
                    $profile->last_name,
                    $profile->extension_name,
                ]))),
            ],
            'family' => $profile->familyMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'last_name' => $member->last_name,
                    'first_name' => $member->first_name,
                    'middle_name' => $member->middle_name,
                    'extension_name' => $member->extension_name,
                    'relationship' => $member->relationship,
                    'birthdate' => $member->birthdate,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->enforceSubmissionCooldown($request);

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:255'],
            'full_address' => ['required', 'string'],
            'contact_number' => ['required', 'string', 'max:255'],
            'sex' => ['required', 'in:Male,Female'],
            'birthdate' => ['required', 'date'],
            'civil_status' => ['required', 'string', 'max:255'],
            'relationship_id' => ['required', 'exists:relationships,id'],
            'bene_last_name' => ['nullable', 'string', 'max:255'],
            'bene_first_name' => ['nullable', 'string', 'max:255'],
            'bene_middle_name' => ['nullable', 'string', 'max:255'],
            'bene_extension_name' => ['nullable', 'string', 'max:255'],
            'bene_sex' => ['nullable', 'in:Male,Female'],
            'bene_birthdate' => ['nullable', 'date'],
            'bene_contact_number' => ['nullable', 'string', 'max:255'],
            'bene_full_address' => ['nullable', 'string'],
            'family_id' => ['array'],
            'family_last_name' => ['array'],
            'family_first_name' => ['array'],
            'family_middle_name' => ['array'],
            'family_extension_name' => ['array'],
            'family_relationship' => ['array'],
            'family_birthdate' => ['array'],
            'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'mode_of_assistance_id' => ['required', 'exists:mode_of_assistances,id'],
            'frequency_case_key' => ['nullable', 'string', 'max:255'],
            'frequency_exception_reason' => ['nullable', 'string'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:10240'],
        ]);

        $this->validateAssistanceSelection(
            (int) $validated['assistance_type_id'],
            (int) $validated['assistance_subtype_id'],
            isset($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null
        );
        $mode = ModeOfAssistance::findOrFail((int) $validated['mode_of_assistance_id']);

        if ((int) $request->relationship_id !== 1) {
            $request->validate([
                'bene_last_name' => ['required', 'string', 'max:255'],
                'bene_first_name' => ['required', 'string', 'max:255'],
                'bene_sex' => ['required', 'in:Male,Female'],
                'bene_birthdate' => ['required', 'date'],
                'bene_contact_number' => ['required', 'string', 'max:255'],
                'bene_full_address' => ['required', 'string'],
            ]);
        }

        $this->validateFamilyRows($request);

        // ================= CLIENT =================
        $client = Client::firstOrNew([
            'user_id' => auth()->id(),
        ]);

        $client->fill([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'extension_name' => $request->extension_name,
            'contact_number' => $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,
            'full_address' => $request->full_address
        ]);
        $client->save();
        $this->familyNetwork->syncClient($client);

        $beneficiaryProfile = null;

        if ((int) $request->relationship_id !== 1) {
            $beneficiaryProfile = $this->upsertBeneficiaryProfile($client, $request);
        }

        $this->ensureNoDuplicateActiveApplication(
            clientId: $client->id,
            beneficiaryProfileId: $beneficiaryProfile?->id,
            subtypeId: (int) $request->assistance_subtype_id,
            detailId: $request->filled('assistance_detail_id') ? (int) $request->assistance_detail_id : null
        );

        $frequencyEvaluation = $this->frequencyEligibility->evaluate([
            'client_id' => $client->id,
            'beneficiary_profile_id' => $beneficiaryProfile?->id,
            'frequency_subject' => $beneficiaryProfile ? 'beneficiary' : 'client',
            'assistance_subtype_id' => (int) $request->assistance_subtype_id,
            'assistance_detail_id' => $request->filled('assistance_detail_id') ? (int) $request->assistance_detail_id : null,
            'frequency_case_key' => $request->frequency_case_key,
        ]);

        if ($frequencyEvaluation['status'] === 'blocked') {
            throw ValidationException::withMessages([
                'assistance_detail_id' => $frequencyEvaluation['message'],
            ]);
        }

        // ================= APPLICATION =================
        $year = now()->format('Y');
        $month = now()->format('m');

        // get latest this month
        $last = Application::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($last && $last->reference_no) {
            $lastNumber = (int) substr($last->reference_no, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // format 000001
        $sequence = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $referenceNo = "APP-$year-$month-$sequence";

        $application = Application::create([
            'client_id' => $client->id,
            'beneficiary_profile_id' => $beneficiaryProfile?->id,
            'user_id' => auth()->id(),

            'reference_no' => $referenceNo, // ✅ IMPORTANT

            'assistance_type_id' => $request->assistance_type_id,
            'assistance_subtype_id' => $request->assistance_subtype_id,
            'assistance_detail_id' => $request->assistance_detail_id,
            'mode_of_assistance_id' => $mode->id,
            'frequency_rule_id' => $frequencyEvaluation['rule']?->id,
            'frequency_basis_application_id' => $frequencyEvaluation['basis_application_id'],
            'frequency_status' => $frequencyEvaluation['status'],
            'frequency_message' => $frequencyEvaluation['message'],
            'frequency_reference_date' => null,
            'frequency_case_key' => $request->frequency_case_key,
            'frequency_exception_reason' => null,
            'frequency_checked_at' => now(),
            'mode_of_assistance' => $mode->name,
            'status' => 'submitted'
        ]);

        // ================= BENEFICIARY =================
        if ($request->relationship_id == 1) {

            // SELF
            Beneficiary::create([
                'application_id' => $application->id,
                'relationship_id' => $request->relationship_id,

                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'extension_name' => $request->extension_name,

                'sex' => $request->sex,
                'birthdate' => $request->birthdate,
                'contact_number' => $request->contact_number,
                'full_address' => $request->full_address
            ]);

        } else {
            // OTHER
            Beneficiary::create([
                'application_id' => $application->id,
                'beneficiary_profile_id' => $beneficiaryProfile->id,
                'relationship_id' => $request->relationship_id,

                'last_name' => $request->bene_last_name,
                'first_name' => $request->bene_first_name,
                'middle_name' => $request->bene_middle_name,
                'extension_name' => $request->bene_extension_name,

                'sex' => $request->bene_sex,
                'birthdate' => $request->bene_birthdate,
                'contact_number' => $request->bene_contact_number,
                'full_address' => $request->bene_full_address
            ]);
        }

        // ================= FAMILY =================
        $this->syncFamilyMembers($client, $application, $request, $beneficiaryProfile);
        $this->familyNetwork->syncApplicationNetwork($application->fresh([
            'client.user',
            'beneficiary',
            'beneficiaryProfile',
            'familyMembers',
        ]));

        
        // ================= DOCUMENTS =================
        if ($request->hasFile('documents')) {

            foreach ($request->file('documents') as $file) {

                // generate unique filename
                $filename = time().'_'.$file->getClientOriginalName();

                // store file
                $path = $file->storeAs('documents', $filename, 'public');

                // save to database
                Document::create([
                    'application_id' => $application->id,
                    'file_name' => $filename,
                    'file_path' => $path
                ]);
            }
        }

        return redirect('/client/dashboard')
            ->with('success', 'Application submitted successfully!')
            ->with('frequency_warning', $frequencyEvaluation['status'] === 'review_required' ? $frequencyEvaluation['message'] : null);
    }

    protected function upsertBeneficiaryProfile(Client $client, Request $request): BeneficiaryProfile
    {
        $profile = $client->beneficiaryProfiles()
            ->firstOrNew([
                'last_name' => trim((string) $request->bene_last_name),
                'first_name' => trim((string) $request->bene_first_name),
                'middle_name' => $request->bene_middle_name,
                'extension_name' => $request->bene_extension_name,
                'birthdate' => $request->bene_birthdate,
            ]);

        $profile->fill([
            'relationship_id' => $request->relationship_id,
            'sex' => $request->bene_sex,
            'contact_number' => $request->bene_contact_number,
            'full_address' => $request->bene_full_address,
        ]);
        $profile->save();
        $this->identityMapping->syncBeneficiaryProfile($profile);

        return $profile;
    }

    protected function syncFamilyMembers(
        Client $client,
        Application $application,
        Request $request,
        ?BeneficiaryProfile $beneficiaryProfile = null
    ): void
    {
        $relation = $beneficiaryProfile
            ? $beneficiaryProfile->familyMembers()
            : $client->familyMembers()->whereNull('beneficiary_profile_id');

        $existingIds = $relation->pluck('id')->all();
        $submittedIds = [];
        $count = count($request->input('family_last_name', []));

        for ($index = 0; $index < $count; $index++) {
            $lastName = trim((string) $request->input("family_last_name.$index"));
            $firstName = trim((string) $request->input("family_first_name.$index"));
            $relationship = $request->input("family_relationship.$index");

            if ($lastName === '' && $firstName === '' && blank($relationship)) {
                continue;
            }

            $payload = [
                'application_id' => $application->id,
                'client_id' => $client->id,
                'beneficiary_profile_id' => $beneficiaryProfile?->id,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $request->input("family_middle_name.$index"),
                'extension_name' => $request->input("family_extension_name.$index"),
                'relationship' => $relationship,
                'birthdate' => $request->input("family_birthdate.$index"),
            ];

            $memberId = $request->input("family_id.$index");

            if ($memberId) {
                $member = $relation->whereKey($memberId)->first();

                if ($member) {
                    $member->update($payload);
                    $this->identityMapping->syncFamilyMember($member);
                    $submittedIds[] = $member->id;
                }

                continue;
            }

            $member = $relation->create($payload);
            $this->identityMapping->syncFamilyMember($member);
            $submittedIds[] = $member->id;
        }

        $toDelete = array_diff($existingIds, $submittedIds);

        if (! empty($toDelete)) {
            $relation->whereIn('id', $toDelete)->delete();
        }
    }

    protected function validateFamilyRows(Request $request): void
    {
        $count = count($request->input('family_last_name', []));
        $validRows = 0;

        for ($index = 0; $index < $count; $index++) {
            $lastName = trim((string) $request->input("family_last_name.$index"));
            $firstName = trim((string) $request->input("family_first_name.$index"));
            $relationship = $request->input("family_relationship.$index");
            $birthdate = $request->input("family_birthdate.$index");

            if ($lastName === '' && $firstName === '' && blank($relationship) && blank($birthdate)) {
                continue;
            }

            $validRows++;

            validator([
                'last_name' => $lastName,
                'first_name' => $firstName,
                'relationship' => $relationship,
                'birthdate' => $birthdate,
            ], [
                'last_name' => ['required', 'string', 'max:255'],
                'first_name' => ['required', 'string', 'max:255'],
                'relationship' => ['required', 'exists:relationships,id'],
                'birthdate' => ['required', 'date'],
            ])->validate();
        }

        if ($validRows === 0) {
            validator([], [
                'family' => ['required'],
            ], [
                'family.required' => 'At least one family member is required.',
            ])->validate();
        }
    }

    protected function validateAssistanceSelection(int $typeId, int $subtypeId, ?int $detailId): void
    {
        $type = AssistanceType::find($typeId);
        $subtype = AssistanceSubtype::with('details')->find($subtypeId);

        if (! $type || ! $subtype || $subtype->assistance_type_id !== $type->id) {
            throw ValidationException::withMessages([
                'assistance_subtype_id' => 'The selected assistance subtype does not belong to the chosen assistance type.',
            ]);
        }

        if ($subtype->details->isNotEmpty() && ! $detailId) {
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

    protected function enforceSubmissionCooldown(Request $request): void
    {
        $key = 'client-application-submit:'.$request->user()->id;
        $maxAttempts = 2;
        $decaySeconds = 600;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = max(1, RateLimiter::availableIn($key));
            $minutes = (int) ceil($seconds / 60);

            throw ValidationException::withMessages([
                'documents' => "Please wait {$minutes} minute(s) before submitting another application.",
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    protected function ensureNoDuplicateActiveApplication(
        int $clientId,
        ?int $beneficiaryProfileId,
        int $subtypeId,
        ?int $detailId
    ): void
    {
        $duplicateQuery = Application::query()
            ->where('client_id', $clientId)
            ->where('assistance_subtype_id', $subtypeId)
            ->whereIn('status', ['submitted', 'under_review', 'for_approval', 'approved']);

        if ($beneficiaryProfileId) {
            $duplicateQuery->where('beneficiary_profile_id', $beneficiaryProfileId);
        } else {
            $duplicateQuery->whereNull('beneficiary_profile_id');
        }

        if ($detailId) {
            $duplicateQuery->where('assistance_detail_id', $detailId);
        } else {
            $duplicateQuery->whereNull('assistance_detail_id');
        }

        $existingApplication = $duplicateQuery->latest('id')->first();

        if (! $existingApplication) {
            return;
        }

        throw ValidationException::withMessages([
            'assistance_detail_id' => 'A similar application is already active under reference no. '
                .$existingApplication->reference_no
                .'. Please wait for that application to be completed before submitting another one.',
        ]);
    }
}
