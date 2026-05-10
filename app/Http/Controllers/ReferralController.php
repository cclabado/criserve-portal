<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationAssistanceRecommendation;
use App\Models\AssistanceDetail;
use App\Models\AssistanceDocumentRequirement;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProfile;
use App\Models\Client;
use App\Models\Document;
use App\Models\FamilyMember;
use App\Models\InstitutionReferral;
use App\Models\ModeOfAssistance;
use App\Models\ReferralInstitution;
use App\Models\ServiceProvider;
use App\Services\AuditLogService;
use App\Services\DocumentSecurityService;
use App\Services\FamilyNetworkService;
use App\Services\IdentityMappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReferralController extends Controller
{
    protected const STATUS_OPTIONS = [
        'pending',
        'acknowledged',
        'in_progress',
        'completed',
        'declined',
    ];

    public function __construct(
        protected AuditLogService $auditLogs,
        protected FamilyNetworkService $familyNetwork,
        protected IdentityMappingService $identityMapping,
        protected DocumentSecurityService $documentSecurity
    ) {
    }

    public function institutionDashboard(Request $request): View
    {
        $institution = $request->user()->referralInstitution;
        abort_unless($institution, 403, 'No referral institution is linked to this account.');

        $query = $this->institutionReferralQuery($institution, $request);
        $referrals = (clone $query)->latest('referred_at')->paginate(10)->withQueryString();
        $stats = $this->buildRecommendationStats((clone $query)->get());
        $submittedQuery = $this->institutionSubmittedReferralQuery($institution, $request);
        $submittedReferrals = (clone $submittedQuery)->latest('submitted_at')->paginate(10, ['*'], 'submitted_page')->withQueryString();
        $submissionStats = $this->buildInstitutionReferralStats((clone $submittedQuery)->get());

        return view('referral.dashboard', [
            'institution' => $institution,
            'referrals' => $referrals,
            'stats' => $stats,
            'submittedReferrals' => $submittedReferrals,
            'submissionStats' => $submissionStats,
            'filters' => $this->filtersFromRequest($request),
            'statusOptions' => self::STATUS_OPTIONS,
            'indexRoute' => route('referral-institution.dashboard'),
            'updateRouteBase' => 'referral-institution.referrals.update',
            'isOfficer' => false,
            'institutionReferralStatusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function officerDashboard(Request $request): View
    {
        $query = $this->officerReferralQuery($request);
        $referrals = (clone $query)->latest('referred_at')->paginate(12)->withQueryString();
        $stats = $this->buildRecommendationStats((clone $query)->get());
        $institutionReferralsQuery = $this->officerInstitutionReferralQuery($request);
        $institutionReferrals = (clone $institutionReferralsQuery)->latest('submitted_at')->paginate(12, ['*'], 'institution_page')->withQueryString();
        $institutionReferralStats = $this->buildInstitutionReferralStats((clone $institutionReferralsQuery)->get());

        return view('referral.dashboard', [
            'institution' => null,
            'referrals' => $referrals,
            'stats' => $stats,
            'institutionReferrals' => $institutionReferrals,
            'institutionReferralStats' => $institutionReferralStats,
            'filters' => $this->filtersFromRequest($request),
            'statusOptions' => self::STATUS_OPTIONS,
            'indexRoute' => route('referral-officer.dashboard'),
            'updateRouteBase' => 'referral-officer.referrals.update',
            'isOfficer' => true,
            'institutions' => ReferralInstitution::where('is_active', true)->orderBy('name')->get(),
            'institutionReferralStatusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function createInstitutionApplication(Request $request): View
    {
        abort_unless($request->user()->referralInstitution, 403, 'No referral institution is linked to this account.');

        return view('client.application-form', [
            'client' => null,
            'formUser' => null,
            'useAccountPrefill' => false,
            'backUrl' => route('referral-institution.dashboard'),
            'formAction' => route('referral-institution.applications.store'),
            'pageTitle' => 'Submit Referred Assistance Application',
            'pageSubtitle' => 'Complete the full client or beneficiary details and upload the supporting documents for referral review.',
            'lookupUrl' => null,
        ]);
    }

    public function storeInstitutionApplication(Request $request): RedirectResponse
    {
        $institution = $request->user()->referralInstitution;
        abort_unless($institution, 403, 'No referral institution is linked to this account.');

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
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'amount_needed' => ['required', 'numeric', 'min:0'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['nullable', 'array'],
            'required_documents.*.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $this->validateAssistanceSelection(
            (int) $validated['assistance_type_id'],
            (int) $validated['assistance_subtype_id'],
            isset($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null
        );

        $documentRequirements = $this->applicableDocumentRequirements(
            (int) $validated['assistance_subtype_id'],
            isset($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null
        );

        $this->validateDocumentUploads($request, $documentRequirements, (float) $validated['amount_needed']);
        $mode = ModeOfAssistance::findOrFail((int) $validated['mode_of_assistance_id']);
        $this->validateModeAmountRule($mode, (float) $validated['amount_needed'], 'amount_needed');
        $serviceProviderId = $this->resolveServiceProviderSelection(
            $request,
            $mode,
            (int) $validated['assistance_subtype_id'],
            isset($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null
        );

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

        $client = Client::create([
            'user_id' => null,
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'extension_name' => $request->extension_name,
            'contact_number' => $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,
            'full_address' => $request->full_address,
        ]);
        $this->familyNetwork->syncClient($client);

        $beneficiaryProfile = null;

        if ((int) $request->relationship_id !== 1) {
            $beneficiaryProfile = $this->createBeneficiaryProfile($client, $request);
        }

        $application = Application::create([
            'client_id' => $client->id,
            'beneficiary_profile_id' => $beneficiaryProfile?->id,
            'user_id' => $request->user()->id,
            'reference_no' => $this->generateReferenceNo(),
            'assistance_type_id' => $request->assistance_type_id,
            'assistance_subtype_id' => $request->assistance_subtype_id,
            'assistance_detail_id' => $request->assistance_detail_id,
            'mode_of_assistance_id' => $mode->id,
            'service_provider_id' => $serviceProviderId,
            'amount_needed' => $validated['amount_needed'],
            'frequency_rule_id' => null,
            'frequency_basis_application_id' => null,
            'frequency_status' => null,
            'frequency_message' => null,
            'frequency_reference_date' => null,
            'frequency_case_key' => null,
            'frequency_exception_reason' => null,
            'frequency_checked_at' => null,
            'mode_of_assistance' => $mode->name,
            'status' => 'submitted',
        ]);

        if ((int) $request->relationship_id === 1) {
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
                'full_address' => $request->full_address,
            ]);
        } else {
            Beneficiary::create([
                'application_id' => $application->id,
                'beneficiary_profile_id' => $beneficiaryProfile?->id,
                'relationship_id' => $request->relationship_id,
                'last_name' => $request->bene_last_name,
                'first_name' => $request->bene_first_name,
                'middle_name' => $request->bene_middle_name,
                'extension_name' => $request->bene_extension_name,
                'sex' => $request->bene_sex,
                'birthdate' => $request->bene_birthdate,
                'contact_number' => $request->bene_contact_number,
                'full_address' => $request->bene_full_address,
            ]);
        }

        $this->syncFamilyMembers($client, $application, $request, $beneficiaryProfile);
        $this->familyNetwork->syncApplicationNetwork($application->fresh([
            'client',
            'beneficiary',
            'beneficiaryProfile',
            'familyMembers',
        ]));

        $this->storeApplicationDocuments($application, $request, $documentRequirements);

        $institutionReferral = InstitutionReferral::create([
            'referral_institution_id' => $institution->id,
            'referred_by_user_id' => $request->user()->id,
            'application_id' => $application->id,
            'subject_type' => (int) $request->relationship_id === 1 ? 'client' : 'beneficiary',
            'client_last_name' => $request->last_name,
            'client_first_name' => $request->first_name,
            'client_middle_name' => $request->middle_name,
            'client_extension_name' => $request->extension_name,
            'client_birthdate' => $request->birthdate,
            'client_contact_number' => $request->contact_number,
            'client_address' => $request->full_address,
            'beneficiary_last_name' => $request->bene_last_name,
            'beneficiary_first_name' => $request->bene_first_name,
            'beneficiary_middle_name' => $request->bene_middle_name,
            'beneficiary_extension_name' => $request->bene_extension_name,
            'beneficiary_birthdate' => $request->bene_birthdate,
            'beneficiary_contact_number' => $request->bene_contact_number,
            'beneficiary_address' => $request->bene_full_address,
            'requested_assistance' => $this->resolveRequestedAssistanceLabel(
                (int) $request->assistance_type_id,
                (int) $request->assistance_subtype_id,
                $request->filled('assistance_detail_id') ? (int) $request->assistance_detail_id : null
            ),
            'case_summary' => null,
            'institution_notes' => 'Submitted as a full referred application.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->auditLogs->log($request, 'institution_referral.application_submitted', $institutionReferral, [
            'institution_id' => $institution->id,
            'application_id' => $application->id,
            'reference_no' => $application->reference_no,
        ]);

        return redirect()
            ->route('referral-institution.dashboard')
            ->with('success', 'Referred application submitted successfully.');
    }

    public function updateReferral(Request $request, ApplicationAssistanceRecommendation $recommendation): RedirectResponse
    {
        $validated = $request->validate([
            'referral_status' => ['required', 'in:'.implode(',', self::STATUS_OPTIONS)],
            'referral_notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if ($user->role === 'referral_institution') {
            abort_unless((int) $user->referral_institution_id === (int) $recommendation->referral_institution_id, 403);
        } else {
            abort_unless($user->role === 'referral_officer', 403);
        }

        $recommendation->update([
            'referral_status' => $validated['referral_status'],
            'referral_notes' => $validated['referral_notes'] ?? null,
            'referral_responded_at' => now(),
        ]);

        $this->auditLogs->log($request, 'referral.status_updated', $recommendation, [
            'referral_status' => $validated['referral_status'],
        ]);

        return redirect()->back()->with('success', 'Referral status updated successfully.');
    }

    public function updateInstitutionReferral(Request $request, InstitutionReferral $institutionReferral): RedirectResponse
    {
        abort_unless($request->user()->role === 'referral_officer', 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUS_OPTIONS)],
            'officer_notes' => ['nullable', 'string'],
        ]);

        $institutionReferral->update([
            'status' => $validated['status'],
            'officer_notes' => $validated['officer_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $this->auditLogs->log($request, 'institution_referral.reviewed', $institutionReferral, [
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Institution referral updated successfully.');
    }

    protected function institutionReferralQuery(ReferralInstitution $institution, Request $request)
    {
        return ApplicationAssistanceRecommendation::query()
            ->with([
                'application.client',
                'application.beneficiary',
                'application.socialWorker',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'referralInstitution',
            ])
            ->where('referral_institution_id', $institution->id)
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('referral_status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('application', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('reference_no', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                    })->orWhereHas('assistanceType', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"));
                });
            });
    }

    protected function officerReferralQuery(Request $request)
    {
        return ApplicationAssistanceRecommendation::query()
            ->with([
                'application.client',
                'application.beneficiary',
                'application.socialWorker',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'referralInstitution',
            ])
            ->whereNotNull('referral_institution_id')
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('referral_status', $request->status))
            ->when($request->filled('institution_id'), fn ($query) => $query->where('referral_institution_id', $request->integer('institution_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('application', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('reference_no', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                    })->orWhereHas('referralInstitution', fn ($institutionQuery) => $institutionQuery->where('name', 'like', "%{$search}%"));
                });
            });
    }

    protected function institutionSubmittedReferralQuery(ReferralInstitution $institution, Request $request)
    {
        return InstitutionReferral::query()
            ->with(['institution', 'referredBy', 'application.assistanceType', 'application.assistanceSubtype'])
            ->where('referral_institution_id', $institution->id)
            ->when($request->filled('submission_status') && $request->input('submission_status') !== 'all', fn ($query) => $query->where('status', $request->input('submission_status')))
            ->when($request->filled('submission_search'), function ($query) use ($request) {
                $search = trim((string) $request->input('submission_search'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('client_first_name', 'like', "%{$search}%")
                        ->orWhere('client_last_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_first_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_last_name', 'like', "%{$search}%")
                        ->orWhere('requested_assistance', 'like', "%{$search}%")
                        ->orWhereHas('application', fn ($applicationQuery) => $applicationQuery->where('reference_no', 'like', "%{$search}%"));
                });
            });
    }

    protected function officerInstitutionReferralQuery(Request $request)
    {
        return InstitutionReferral::query()
            ->with(['institution', 'referredBy', 'application.assistanceType', 'application.assistanceSubtype'])
            ->when($request->filled('submission_status') && $request->input('submission_status') !== 'all', fn ($query) => $query->where('status', $request->input('submission_status')))
            ->when($request->filled('institution_id'), fn ($query) => $query->where('referral_institution_id', $request->integer('institution_id')))
            ->when($request->filled('submission_search'), function ($query) use ($request) {
                $search = trim((string) $request->input('submission_search'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('client_first_name', 'like', "%{$search}%")
                        ->orWhere('client_last_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_first_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_last_name', 'like', "%{$search}%")
                        ->orWhere('requested_assistance', 'like', "%{$search}%")
                        ->orWhereHas('application', fn ($applicationQuery) => $applicationQuery->where('reference_no', 'like', "%{$search}%"))
                        ->orWhereHas('institution', fn ($institutionQuery) => $institutionQuery->where('name', 'like', "%{$search}%"));
                });
            });
    }

    protected function createBeneficiaryProfile(Client $client, Request $request): BeneficiaryProfile
    {
        $profile = $client->beneficiaryProfiles()->create([
            'relationship_id' => $request->relationship_id,
            'last_name' => trim((string) $request->bene_last_name),
            'first_name' => trim((string) $request->bene_first_name),
            'middle_name' => $request->bene_middle_name,
            'extension_name' => $request->bene_extension_name,
            'sex' => $request->bene_sex,
            'birthdate' => $request->bene_birthdate,
            'contact_number' => $request->bene_contact_number,
            'full_address' => $request->bene_full_address,
        ]);

        $this->identityMapping->syncBeneficiaryProfile($profile);

        return $profile;
    }

    protected function syncFamilyMembers(
        Client $client,
        Application $application,
        Request $request,
        ?BeneficiaryProfile $beneficiaryProfile = null
    ): void {
        $masterRelation = $beneficiaryProfile
            ? $beneficiaryProfile->familyMembers()->whereNull('application_id')
            : $client->familyMembers()->whereNull('beneficiary_profile_id')->whereNull('application_id');

        $snapshotRows = [];
        $count = count($request->input('family_last_name', []));

        for ($index = 0; $index < $count; $index++) {
            $lastName = trim((string) $request->input("family_last_name.$index"));
            $firstName = trim((string) $request->input("family_first_name.$index"));
            $relationship = $request->input("family_relationship.$index");

            if ($lastName === '' && $firstName === '' && blank($relationship)) {
                continue;
            }

            $member = $masterRelation->create([
                'application_id' => null,
                'client_id' => $client->id,
                'beneficiary_profile_id' => $beneficiaryProfile?->id,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $request->input("family_middle_name.$index"),
                'extension_name' => $request->input("family_extension_name.$index"),
                'relationship' => $relationship,
                'birthdate' => $request->input("family_birthdate.$index"),
            ]);

            $this->identityMapping->syncFamilyMember($member);

            $snapshotRows[] = $member->only([
                'client_id',
                'linked_user_id',
                'person_id',
                'beneficiary_profile_id',
                'last_name',
                'first_name',
                'middle_name',
                'extension_name',
                'relationship',
                'birthdate',
            ]);
        }

        $application->applicationFamilyMembers()->delete();

        foreach ($snapshotRows as $snapshotRow) {
            $application->applicationFamilyMembers()->create($snapshotRow);
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

    protected function applicableDocumentRequirements(int $subtypeId, ?int $detailId = null)
    {
        return AssistanceDocumentRequirement::query()
            ->active()
            ->forSelection($subtypeId, $detailId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function validateDocumentUploads(Request $request, $requirements, float $amountNeeded): void
    {
        $requiredRequirements = $requirements->filter(
            fn (AssistanceDocumentRequirement $requirement) => $requirement->isRequiredForAmount($amountNeeded)
        );

        $messages = [];

        foreach ($requiredRequirements as $requirement) {
            if (! $request->hasFile("required_documents.{$requirement->id}")) {
                $messages["required_documents.{$requirement->id}"] = "Upload the required document for {$requirement->name}.";
            }
        }

        if ($requiredRequirements->isEmpty()) {
            $genericDocuments = $request->file('documents', []);

            if (count($genericDocuments) === 0) {
                $messages['documents'] = 'Upload at least one supporting document before submitting.';
            }
        }

        if (! empty($messages)) {
            throw ValidationException::withMessages($messages);
        }
    }

    protected function storeApplicationDocuments(Application $application, Request $request, $requirements): void
    {
        foreach ($requirements as $requirement) {
            if (! $request->hasFile("required_documents.{$requirement->id}")) {
                continue;
            }

            foreach ($request->file("required_documents.{$requirement->id}") as $file) {
                $storedDocument = $this->documentSecurity->secureStore($file);

                Document::create([
                    'application_id' => $application->id,
                    'document_requirement_id' => $requirement->id,
                    'document_type' => $requirement->name,
                    'file_name' => $storedDocument['file_name'],
                    'file_path' => $storedDocument['path'],
                    'storage_disk' => $storedDocument['disk'],
                    'mime_type' => $storedDocument['mime_type'],
                    'file_size' => $storedDocument['file_size'],
                    'file_hash' => $storedDocument['file_hash'],
                ]);
            }
        }

        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents') as $file) {
            $storedDocument = $this->documentSecurity->secureStore($file);

            Document::create([
                'application_id' => $application->id,
                'document_type' => 'Additional Supporting Document',
                'file_name' => $storedDocument['file_name'],
                'file_path' => $storedDocument['path'],
                'storage_disk' => $storedDocument['disk'],
                'mime_type' => $storedDocument['mime_type'],
                'file_size' => $storedDocument['file_size'],
                'file_hash' => $storedDocument['file_hash'],
            ]);
        }
    }

    protected function resolveServiceProviderSelection(Request $request, ModeOfAssistance $mode, ?int $subtypeId = null, ?int $detailId = null): ?int
    {
        $serviceProviderId = $request->filled('service_provider_id')
            ? (int) $request->input('service_provider_id')
            : null;

        if (strtolower(trim((string) $mode->name)) !== 'guarantee letter') {
            return null;
        }

        if (! $serviceProviderId) {
            throw ValidationException::withMessages([
                'service_provider_id' => 'Please select a service provider when the mode of assistance is Guarantee Letter.',
            ]);
        }

        $provider = ServiceProvider::query()
            ->whereKey($serviceProviderId)
            ->where('is_active', true)
            ->first();

        if (! $provider) {
            throw ValidationException::withMessages([
                'service_provider_id' => 'The selected service provider is unavailable.',
            ]);
        }

        $this->validateServiceProviderCategoryMatch($provider, $subtypeId, $detailId);

        return $provider->id;
    }

    protected function validateModeAmountRule(ModeOfAssistance $mode, float $amount, string $field): void
    {
        $minimumAmount = $mode->minimum_amount !== null ? (float) $mode->minimum_amount : null;
        $maximumAmount = $mode->maximum_amount !== null ? (float) $mode->maximum_amount : null;

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

    protected function validateServiceProviderCategoryMatch(ServiceProvider $provider, ?int $subtypeId, ?int $detailId): void
    {
        $subtype = $subtypeId ? AssistanceSubtype::find($subtypeId) : null;
        $detail = $detailId ? AssistanceDetail::find($detailId) : null;
        $relevantCategories = ServiceProvider::inferRelevantCategories($subtype?->name, $detail?->name);

        if ($relevantCategories === []) {
            return;
        }

        $hasAnyMatchingProvider = ServiceProvider::query()
            ->where('is_active', true)
            ->get()
            ->contains(fn (ServiceProvider $serviceProvider) => collect($serviceProvider->categories ?? [])->intersect($relevantCategories)->isNotEmpty());

        if (! $hasAnyMatchingProvider) {
            return;
        }

        if (! collect($provider->categories ?? [])->intersect($relevantCategories)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_provider_id' => 'The selected service provider does not match the required category for this assistance.',
            ]);
        }
    }

    protected function generateReferenceNo(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $last = Application::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $last && $last->reference_no
            ? ((int) substr($last->reference_no, -6)) + 1
            : 1;

        return 'APP-'.$year.'-'.$month.'-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function resolveRequestedAssistanceLabel(int $typeId, int $subtypeId, ?int $detailId): string
    {
        $type = AssistanceType::find($typeId)?->name;
        $subtype = AssistanceSubtype::find($subtypeId)?->name;
        $detail = $detailId ? AssistanceDetail::find($detailId)?->name : null;

        return collect([$type, $subtype, $detail])->filter()->implode(' - ');
    }

    protected function buildRecommendationStats($referrals): array
    {
        return [
            'total' => $referrals->count(),
            'pending' => $referrals->where('referral_status', 'pending')->count(),
            'in_progress' => $referrals->where('referral_status', 'in_progress')->count(),
            'completed' => $referrals->where('referral_status', 'completed')->count(),
        ];
    }

    protected function buildInstitutionReferralStats($referrals): array
    {
        return [
            'total' => $referrals->count(),
            'pending' => $referrals->where('status', 'pending')->count(),
            'in_progress' => $referrals->where('status', 'in_progress')->count(),
            'completed' => $referrals->where('status', 'completed')->count(),
        ];
    }

    protected function filtersFromRequest(Request $request): array
    {
        return [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', 'all'),
            'institution_id' => (string) $request->input('institution_id', ''),
            'submission_search' => (string) $request->input('submission_search', ''),
            'submission_status' => (string) $request->input('submission_status', 'all'),
        ];
    }
}
