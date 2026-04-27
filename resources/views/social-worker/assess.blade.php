@extends('layouts.app')

@section('content')

@php
    $socialWorker = auth()->user();
    $googleConnected = $socialWorker?->hasGoogleCalendarConnection();
    $currentFrequencyStatus = $frequencyPreview['status'] ?? $application->frequency_status;
    $currentFrequencyMessage = $frequencyPreview['message'] ?? $application->frequency_message ?? $application->frequencyRule?->notes;
    $basisApplication = $application->frequencyBasisApplication;
    $basisReleasedDate = $basisApplication?->updated_at?->format('M d, Y') ?? $basisApplication?->created_at?->format('M d, Y');
    $defaultCancellationReason = $basisApplication
        ? 'Previously availed assistance on '.$basisReleasedDate.' under application reference no. '.$basisApplication->reference_no.'.'
        : 'Application cancelled due to previous assistance availment under the frequency of assistance policy.';
    $frequencyBadgeClasses = [
        'eligible' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'review_required' => 'bg-amber-100 text-amber-800 border border-amber-200',
        'blocked' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'overridden' => 'bg-sky-100 text-sky-800 border border-sky-200',
        'not_applicable' => 'bg-slate-100 text-slate-700 border border-slate-200',
    ];
    $frequencyRuleMap = $assistanceSubtypes->mapWithKeys(function ($subtype) {
        return [
            (string) $subtype->id => [
                'subtypeRule' => $subtype->frequencyRule ? [
                    'notes' => $subtype->frequencyRule->notes,
                    'requires_reference_date' => (bool) $subtype->frequencyRule->requires_reference_date,
                    'requires_case_key' => (bool) $subtype->frequencyRule->requires_case_key,
                    'allows_exception_request' => (bool) $subtype->frequencyRule->allows_exception_request,
                ] : null,
                'detailRules' => $subtype->details->mapWithKeys(function ($detail) {
                    return [
                        (string) $detail->id => $detail->frequencyRule ? [
                            'notes' => $detail->frequencyRule->notes,
                            'requires_reference_date' => (bool) $detail->frequencyRule->requires_reference_date,
                            'requires_case_key' => (bool) $detail->frequencyRule->requires_case_key,
                            'allows_exception_request' => (bool) $detail->frequencyRule->allows_exception_request,
                        ] : null,
                    ];
                })->toArray(),
            ],
        ];
    })->toArray();
@endphp

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div>
        <a href="/social-worker/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
            ← BACK TO DASHBOARD
        </a>

        <h1 class="text-3xl font-bold text-[#234E70]">
            Initial Assessment
        </h1>

        <p class="text-gray-500">
            Reference No: {{ $application->reference_no }}
        </p>
    </div>

    <form id="assessmentForm" method="POST" action="{{ route('socialworker.assess.update', $application->id) }}">
        @csrf
        <input type="hidden" name="assessment_action" id="assessmentActionInput" value="save">

        <!-- STEPS -->
        <div class="grid grid-cols-3 gap-4">

            <div id="step-indicator-1" onclick="goToStep(1)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 1</p>
                <p class="font-semibold">Client / Beneficiary</p>
            </div>

            <div id="step-indicator-2" onclick="goToStep(2)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 2</p>
                <p class="font-semibold">Assistance & Documents</p>
            </div>

            <div id="step-indicator-3" onclick="goToStep(3)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 3</p>
                <p class="font-semibold">Notes & Schedule</p>
            </div>

        </div>

        <!-- ================= STEP 1 ================= -->
        <div id="step-1" class="step-content bg-white p-6 rounded-xl shadow space-y-6">

            <!-- CLIENT -->
            <div>
                <h2 class="text-lg font-bold text-[#234E70] mb-4">Client Information</h2>

                <div class="grid grid-cols-4 gap-4">

                    <div>
                        <label class="label">Last Name</label>
                        <input name="client_last_name" class="input w-full"
                            value="{{ $application->client->last_name }}">
                    </div>

                    <div>
                        <label class="label">First Name</label>
                        <input name="client_first_name" class="input w-full"
                            value="{{ $application->client->first_name }}">
                    </div>

                    <div>
                        <label class="label">Middle Name</label>
                        <input name="client_middle_name" class="input w-full"
                            value="{{ $application->client->middle_name }}">
                    </div>

                    <div>
                        <label class="label">Extension</label>
                        <input name="client_extension_name" class="input w-full"
                            value="{{ $application->client->extension_name }}">
                    </div>

                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">

                    <div>
                        <label class="label">Address</label>
                        <input name="client_address" class="input w-full"
                            value="{{ $application->client->full_address }}">
                    </div>

                    <div>
                        <label class="label">Contact Number</label>
                        <input name="client_contact_number" class="input w-full"
                            value="{{ $application->client->contact_number }}">
                    </div>

                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">

                    <div>
                        <label class="label">Sex</label>
                        <select name="client_sex" class="input w-full">
                            <option value="Male"
                                {{ $application->client->sex == 'Male' ? 'selected' : '' }}>
                                Male
                            </option>

                            <option value="Female"
                                {{ $application->client->sex == 'Female' ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Birthdate</label>
                        <input type="date" name="client_birthdate" class="input w-full"
                            value="{{ $application->client->birthdate }}">
                    </div>

                    <div>
                        <label class="label">Civil Status</label>
                        <select name="client_civil_status" class="input w-full">
                            <option value="Single"
                                {{ $application->client->civil_status == 'Single' ? 'selected' : '' }}>
                                Single
                            </option>

                            <option value="Married"
                                {{ $application->client->civil_status == 'Married' ? 'selected' : '' }}>
                                Married
                            </option>

                            <option value="Widowed"
                                {{ $application->client->civil_status == 'Widowed' ? 'selected' : '' }}>
                                Widowed
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <hr>

            <!-- BENEFICIARY -->
            <div>
                <h2 class="text-lg font-bold text-[#234E70] mb-4">Beneficiary Information</h2>

                <div class="grid grid-cols-4 gap-4">

                    <div>
                        <label class="label">Last Name</label>
                        <input name="beneficiary_last_name" class="input w-full"
                            value="{{ $application->beneficiary->last_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">First Name</label>
                        <input name="beneficiary_first_name" class="input w-full"
                            value="{{ $application->beneficiary->first_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Middle Name</label>
                        <input name="beneficiary_middle_name" class="input w-full"
                            value="{{ $application->beneficiary->middle_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Extension</label>
                        <input name="beneficiary_extension_name" class="input w-full"
                            value="{{ $application->beneficiary->extension_name ?? '' }}">
                    </div>

                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">

                    <div>
                        <label class="label">Sex</label>
                        <select name="beneficiary_sex" class="input w-full">
                            <option value="Male"
                                {{ ($application->beneficiary->sex ?? '') == 'Male' ? 'selected' : '' }}>
                                Male
                            </option>

                            <option value="Female"
                                {{ ($application->beneficiary->sex ?? '') == 'Female' ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Birthdate</label>
                        <input type="date" name="beneficiary_birthdate" class="input w-full"
                            value="{{ $application->beneficiary->birthdate ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Contact Number</label>
                        <input name="beneficiary_contact_number" class="input w-full"
                            value="{{ $application->beneficiary->contact_number ?? '' }}">
                    </div>

                </div>

                <div class="mt-4">
                    <label class="label">Address</label>
                    <input name="beneficiary_address" class="input w-full"
                        value="{{ $application->beneficiary->full_address ?? '' }}">
                </div>
            </div>

            <hr>

            <!-- FAMILY -->
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-[#234E70]">Family Composition</h2>
                    <p class="text-sm text-gray-500">
                        {{ $application->householdProfileLabel() }}
                    </p>

                    @if($application->beneficiary?->relationshipData)
                        <p class="text-xs text-gray-400 mt-1">
                            Client's relationship to beneficiary: {{ $application->beneficiary->relationshipData->name }}
                        </p>
                    @endif
                </div>

                <button type="button"
                    onclick="addFamily()"
                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    + Add Family Member
                </button>
            </div>

            <div class="bg-gray-100 rounded-xl px-5 py-3 grid grid-cols-5 text-xs text-gray-500 font-semibold">
                <div class="col-span-2">FULL NAME</div>
                <div>RELATIONSHIP</div>
                <div>DATE OF BIRTH</div>
                <div class="text-center">ACTIONS</div>
            </div>

            <div id="familyContainer" class="space-y-2 mt-2">

                @foreach($householdMembers ?? [] as $index => $member)

                <div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

                    <input type="hidden" name="family[{{ $index }}][id]" value="{{ $member->id }}">

                    <div class="col-span-2 grid grid-cols-3 gap-2">
                        <input name="family[{{ $index }}][last_name]" class="input text-sm"
                            placeholder="Last" value="{{ $member->last_name }}">

                        <input name="family[{{ $index }}][first_name]" class="input text-sm"
                            placeholder="First" value="{{ $member->first_name }}">

                        <input name="family[{{ $index }}][middle_name]" class="input text-sm"
                            placeholder="Middle" value="{{ $member->middle_name }}">
                    </div>

                    <div>
                        <select name="family[{{ $index }}][relationship]" class="input text-sm w-full">
                            @foreach($relationships as $rel)
                            <option value="{{ $rel->id }}"
                                {{ $member->relationship == $rel->id ? 'selected' : '' }}>
                                {{ $rel->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <input type="date"
                            name="family[{{ $index }}][birthdate]"
                            class="input text-sm w-full"
                            value="{{ $member->birthdate }}">
                    </div>

                    <div class="flex justify-center">
                        <button type="button"
                            onclick="removeRow(this)"
                            class="text-red-500 hover:text-red-700">

                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>

                </div>

                @endforeach

            </div>

            <p class="text-sm text-gray-500">
                Updates here modify the saved household for the {{ $application->usesBeneficiaryHousehold() ? 'beneficiary profile' : 'client account' }}, so the same family composition will appear in future related applications.
            </p>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <div>
                <h2 class="font-bold text-[#234E70] mb-4">Assistance Information</h2>

                <div class="grid grid-cols-4 gap-4">

                    <div>
                        <label class="label">Type of Assistance</label>
                        <div class="select-shell">
                        <select name="assistance_type_id" id="assistanceTypeSelect" class="input input-select w-full">
                            <option value="">Select type</option>
                            @foreach($assistanceTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ $application->assistance_type_id == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                            @endforeach
                        </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Specific Assistance</label>
                        <div class="select-shell">
                        <select name="assistance_subtype_id" id="assistanceSubtypeSelect" class="input input-select w-full">
                            <option value="">Select subtype</option>
                            @foreach($assistanceSubtypes as $sub)
                            <option value="{{ $sub->id }}"
                                data-type-id="{{ $sub->assistance_type_id }}"
                                {{ $application->assistance_subtype_id == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                            </option>
                            @endforeach
                        </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Assistance Detail</label>
                        <div class="select-shell">
                        <select name="assistance_detail_id" id="assistanceDetailSelect" class="input input-select w-full">
                            <option value="">Select detail</option>
                            @foreach($assistanceSubtypes as $sub)
                                @foreach($sub->details as $detail)
                                <option value="{{ $detail->id }}"
                                    data-subtype-id="{{ $sub->id }}"
                                    {{ $application->assistance_detail_id == $detail->id ? 'selected' : '' }}>
                                    {{ $detail->name }}
                                </option>
                                @endforeach
                            @endforeach
                        </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Mode of Assistance</label>
                        <div class="select-shell">
                        <select name="mode_of_assistance_id" class="input input-select w-full">
                            <option value="">Select mode</option>
                            @foreach($modesOfAssistance as $mode)
                            <option value="{{ $mode->id }}"
                                {{ $application->mode_of_assistance_id == $mode->id ? 'selected' : '' }}>
                                {{ $mode->name }}
                            </option>
                            @endforeach
                        </select>
                        </div>
                    </div>

                </div>

                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-4">
                    <div>
                        <h3 class="font-semibold text-amber-900">Frequency of Assistance Review</h3>
                        <p id="frequencyRuleNotes" class="mt-1 text-sm text-amber-800">
                            {{ $currentFrequencyMessage ?: ($application->frequencyRule->notes ?? 'Select an assistance item to review the applicable frequency rule.') }}
                        </p>
                    </div>

                    @if($currentFrequencyStatus)
                    <div class="rounded-lg bg-white/70 px-4 py-3 text-sm text-amber-900">
                        <span class="font-semibold">Current Status:</span>
                        <span class="ml-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase {{ $frequencyBadgeClasses[$currentFrequencyStatus] ?? $frequencyBadgeClasses['not_applicable'] }}">
                            {{ str_replace('_', ' ', $currentFrequencyStatus) }}
                        </span>
                        @if($basisApplication)
                            <span class="block mt-1 text-amber-800">
                                Based on: {{ $basisApplication->reference_no }} released on {{ $basisReleasedDate }}
                            </span>
                        @endif
                    </div>
                    @endif

                    @if($basisApplication)
                    <div class="rounded-lg border border-amber-200 bg-white px-4 py-4 text-sm text-amber-900 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-amber-900">Previous Released Assistance Found</p>
                                <p class="mt-1 text-amber-800">
                                    Reference No. {{ $basisApplication->reference_no }} released on {{ $basisReleasedDate }}
                                </p>
                            </div>

                            <button type="button"
                               id="openPreviousAssistanceModalBtn"
                               class="inline-flex items-center rounded-lg border border-amber-300 px-4 py-2 font-semibold text-amber-900 hover:bg-amber-100">
                                View Previous Assistance Details
                            </button>
                        </div>

                        <div>
                            <label class="label">Cancellation Reason</label>
                            <textarea name="cancellation_reason"
                                class="input w-full h-24"
                                placeholder="Explain why this application should be cancelled.">{{ old('cancellation_reason', $defaultCancellationReason) }}</textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button"
                                id="cancelFrequencyBtn"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                Cancel Application
                            </button>
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div id="frequencyCaseKeyWrap" style="display:none;">
                            <label class="label">Incident / Admission Reference</label>
                            <input type="text"
                                name="frequency_case_key"
                                class="input w-full"
                                value="{{ old('frequency_case_key', $application->frequency_case_key) }}"
                                placeholder="Case number, incident label, admission reference">
                        </div>
                    </div>

                    <div id="frequencyOverrideWrap" @if(!in_array($currentFrequencyStatus, ['blocked', 'overridden'], true) && !($application->frequencyRule?->allows_exception_request ?? false)) style="display:none;" @endif>
                        <label class="label">Justification</label>
                        <textarea name="frequency_override_reason"
                            class="input w-full h-24"
                            placeholder="Explain why this case should still proceed under frequency review.">{{ old('frequency_override_reason', $application->frequency_override_reason) }}</textarea>
                    </div>
                </div>
            </div>

            <hr>

            <div>
                <h2 class="font-bold text-[#234E70] mb-4">Uploaded Documents</h2>

                @forelse($application->documents ?? [] as $file)

                <div class="bg-gray-50 p-4 rounded-lg flex items-center justify-between mb-3">

                    <div class="flex items-center gap-3">

                        <a href="{{ route('documents.show', $file->id) }}"
                            class="px-3 py-2 bg-gray-200 rounded-lg text-sm hover:bg-gray-300">
                            Open
                        </a>

                        <p class="font-medium text-gray-700">
                            {{ $file->file_name ?? $file->filename }}
                        </p>

                    </div>

                    <div class="w-72">
                        <input type="text"
                            name="remarks[{{ $file->id }}]"
                            value="{{ $file->remarks ?? '' }}"
                            class="input w-full"
                            placeholder="Add remarks...">
                    </div>

                </div>

                @empty
                <p class="text-gray-400">No documents uploaded.</p>
                @endforelse

            </div>

        </div>

        <!-- ================= STEP 3 ================= -->
        <div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-4">

            <h2 class="font-bold text-[#234E70]">Notes & Schedule</h2>

            <div class="rounded-2xl border {{ $googleConnected ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-4">
                <p class="font-semibold {{ $googleConnected ? 'text-emerald-800' : 'text-amber-800' }}">
                    {{ $googleConnected ? 'Google Calendar connected' : 'Google Calendar not connected' }}
                </p>

                <p class="mt-1 text-sm {{ $googleConnected ? 'text-emerald-700' : 'text-amber-700' }}">
                    @if($googleConnected)
                        Saving a schedule will automatically create or update the social worker's Google Calendar event and generate a Google Meet link.
                    @else
                        Connect your Google account in
                        <a href="{{ route('profile.edit') }}" class="font-semibold underline">Profile Settings</a>
                        to auto-generate the calendar event and Meet link. Until then, you can still enter a meeting link manually.
                    @endif
                </p>
            </div>

            <div>
                <label class="label">Assessment Notes</label>
                <textarea name="notes"
                    class="input w-full h-32">{{ $application->notes }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Schedule Date</label>
                    <input type="datetime-local"
                        name="schedule_date"
                        class="input w-full"
                        value="{{ $application->schedule_date ? \Carbon\Carbon::parse($application->schedule_date)->format('Y-m-d\TH:i') : '' }}">
                </div>

                <div>
                    <label class="label">Meeting Link</label>
                    <input type="text"
                        name="meeting_link"
                        class="input w-full"
                        value="{{ $application->meeting_link }}"
                        placeholder="{{ $googleConnected ? 'Generated automatically after saving the schedule' : 'Paste a manual meeting link if Google is not connected' }}"
                        @if($googleConnected) readonly @endif>

                    @if($application->google_calendar_event_link)
                        <a href="{{ $application->google_calendar_event_link }}"
                           target="_blank"
                           class="mt-2 inline-flex text-sm font-semibold text-[#234E70] underline">
                            Open Google Calendar Event
                        </a>
                    @endif
                </div>

            </div>

        </div>

        <!-- BUTTONS -->
        <div class="flex justify-between items-center">

            <button type="button"
                id="backBtn"
                class="px-5 py-2 bg-gray-200 rounded-lg hidden">
                ← Back
            </button>

            <button type="button"
                id="nextBtn"
                class="px-6 py-3 bg-[#234E70] text-white rounded-lg shadow hover:bg-[#18384f]">
                Next →
            </button>

        </div>

    </form>

    @if($basisApplication)
    <div id="previousAssistanceModal" class="fixed inset-0 z-50 hidden">
        <div id="previousAssistanceModalBackdrop" class="absolute inset-0 bg-slate-900/55"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Previous Released Assistance</p>
                        <h2 class="mt-1 text-2xl font-bold text-[#234E70]">{{ $basisApplication->reference_no }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Released on {{ $basisReleasedDate }}</p>
                    </div>

                    <button type="button"
                        id="closePreviousAssistanceModalBtn"
                        class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        Close
                    </button>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="label">Client</span>
                            <p class="font-semibold text-slate-800">
                                {{ $basisApplication->client?->first_name }} {{ $basisApplication->client?->last_name }}
                            </p>
                        </div>

                        <div>
                            <span class="label">Status</span>
                            <p class="font-semibold text-emerald-700 uppercase">
                                {{ str_replace('_', ' ', $basisApplication->status) }}
                            </p>
                        </div>

                        <div>
                            <span class="label">Type of Assistance</span>
                            <p class="font-semibold text-slate-800">{{ $basisApplication->assistanceType?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="label">Specific Assistance</span>
                            <p class="font-semibold text-slate-800">{{ $basisApplication->assistanceSubtype?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="label">Assistance Detail</span>
                            <p class="font-semibold text-slate-800">{{ $basisApplication->assistanceDetail?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="label">Mode of Assistance</span>
                            <p class="font-semibold text-slate-800">{{ $basisApplication->modeOfAssistance?->name ?? $basisApplication->mode_of_assistance ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="label">Final Amount</span>
                            <p class="font-semibold text-slate-800">PHP {{ number_format($basisApplication->final_amount ?? $basisApplication->recommended_amount ?? 0, 2) }}</p>
                        </div>

                        <div>
                            <span class="label">Frequency Note</span>
                            <p class="font-semibold text-slate-800">{{ $basisApplication->frequency_message ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm">
                        <p class="font-semibold text-slate-700">Notes</p>
                        <p class="mt-2 text-slate-600">{{ $basisApplication->notes ?: 'No notes recorded.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</main>

<!-- JS -->
<script>
let currentStep = 1;
const totalSteps = 3;

function updateSteps() {

    for (let i = 1; i <= totalSteps; i++) {
        document.getElementById('step-' + i).classList.add('hidden');
        document.getElementById('step-indicator-' + i).classList.remove('active');
    }

    document.getElementById('step-' + currentStep).classList.remove('hidden');
    document.getElementById('step-indicator-' + currentStep).classList.add('active');

    document.getElementById('backBtn').style.display =
        currentStep === 1 ? 'none' : 'inline-block';

    document.getElementById('nextBtn').innerText =
        currentStep === totalSteps ? 'Save Assessment' : 'Next →';
}

function goToStep(step) {
    currentStep = step;
    updateSteps();
}

document.getElementById('nextBtn').addEventListener('click', function () {

    if (currentStep < totalSteps) {
        currentStep++;
        updateSteps();
    } else {
        document.getElementById('assessmentForm').submit();
    }
});

document.getElementById('backBtn').addEventListener('click', function () {
    currentStep--;
    updateSteps();
});

updateSteps();

const assessmentActionInput = document.getElementById('assessmentActionInput');
const cancelFrequencyBtn = document.getElementById('cancelFrequencyBtn');
const previousAssistanceModal = document.getElementById('previousAssistanceModal');
const openPreviousAssistanceModalBtn = document.getElementById('openPreviousAssistanceModalBtn');
const closePreviousAssistanceModalBtn = document.getElementById('closePreviousAssistanceModalBtn');
const previousAssistanceModalBackdrop = document.getElementById('previousAssistanceModalBackdrop');

const assistanceTypeSelect = document.getElementById('assistanceTypeSelect');
const assistanceSubtypeSelect = document.getElementById('assistanceSubtypeSelect');
const assistanceDetailSelect = document.getElementById('assistanceDetailSelect');
const frequencyRuleNotes = document.getElementById('frequencyRuleNotes');
const frequencyCaseKeyWrap = document.getElementById('frequencyCaseKeyWrap');
const frequencyOverrideWrap = document.getElementById('frequencyOverrideWrap');
const frequencyRuleMap = @json($frequencyRuleMap);

function currentFrequencyRule() {
    const subtypeId = assistanceSubtypeSelect?.value || '';
    const detailId = assistanceDetailSelect?.value || '';
    const subtypeEntry = frequencyRuleMap[subtypeId];

    if (!subtypeEntry) {
        return null;
    }

    return subtypeEntry.detailRules?.[detailId] || subtypeEntry.subtypeRule || null;
}

function updateFrequencyRuleUI() {
    const rule = currentFrequencyRule();

    if (frequencyRuleNotes) {
        frequencyRuleNotes.textContent = rule?.notes || 'Select an assistance item to review the applicable frequency rule.';
    }

    if (frequencyCaseKeyWrap) {
        frequencyCaseKeyWrap.style.display = rule?.requires_case_key ? 'block' : 'none';
    }

    if (frequencyOverrideWrap) {
        const currentStatus = @json($currentFrequencyStatus);
        const shouldShow = ['blocked', 'overridden'].includes(currentStatus) || !!rule?.allows_exception_request;
        frequencyOverrideWrap.style.display = shouldShow ? 'block' : 'none';
    }
}

function updateSubtypeOptions() {
    const selectedType = assistanceTypeSelect?.value || '';

    Array.from(assistanceSubtypeSelect.options).forEach((option, index) => {
        if (index === 0) {
            return;
        }

        const matches = option.dataset.typeId === selectedType;
        option.hidden = !matches;

        if (!matches && option.selected) {
            assistanceSubtypeSelect.value = '';
        }
    });

    updateDetailOptions();
    updateFrequencyRuleUI();
}

function updateDetailOptions() {
    const selectedSubtype = assistanceSubtypeSelect?.value || '';
    let hasVisibleDetails = false;

    Array.from(assistanceDetailSelect.options).forEach((option, index) => {
        if (index === 0) {
            option.text = selectedSubtype ? 'Select detail' : 'No detail required';
            return;
        }

        const matches = option.dataset.subtypeId === selectedSubtype;
        option.hidden = !matches;

        if (matches) {
            hasVisibleDetails = true;
        }

        if (!matches && option.selected) {
            assistanceDetailSelect.value = '';
        }
    });

    if (!hasVisibleDetails) {
        assistanceDetailSelect.value = '';
    }

    assistanceDetailSelect.disabled = !hasVisibleDetails;
    updateFrequencyRuleUI();
}

assistanceTypeSelect?.addEventListener('change', updateSubtypeOptions);
assistanceSubtypeSelect?.addEventListener('change', updateDetailOptions);
assistanceDetailSelect?.addEventListener('change', updateFrequencyRuleUI);
updateSubtypeOptions();

cancelFrequencyBtn?.addEventListener('click', function () {
    if (assessmentActionInput) {
        assessmentActionInput.value = 'cancel_due_to_frequency';
    }

    if (confirm('Cancel this application based on the previous released assistance record?')) {
        document.getElementById('assessmentForm').submit();
        return;
    }

    if (assessmentActionInput) {
        assessmentActionInput.value = 'save';
    }
});

function togglePreviousAssistanceModal(show) {
    if (!previousAssistanceModal) {
        return;
    }

    previousAssistanceModal.classList.toggle('hidden', !show);
    document.body.classList.toggle('overflow-hidden', show);
}

openPreviousAssistanceModalBtn?.addEventListener('click', function () {
    togglePreviousAssistanceModal(true);
});

closePreviousAssistanceModalBtn?.addEventListener('click', function () {
    togglePreviousAssistanceModal(false);
});

previousAssistanceModalBackdrop?.addEventListener('click', function () {
    togglePreviousAssistanceModal(false);
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        togglePreviousAssistanceModal(false);
    }
});

let familyIndex = {{ count($application->familyMembers ?? []) }};

function addFamily() {

    let html = `
    <div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

        <div class="col-span-2 grid grid-cols-3 gap-2">
            <input name="family[\${familyIndex}][last_name]" class="input text-sm" placeholder="Last">
            <input name="family[\${familyIndex}][first_name]" class="input text-sm" placeholder="First">
            <input name="family[\${familyIndex}][middle_name]" class="input text-sm" placeholder="Middle">
        </div>

        <div>
            <select name="family[\${familyIndex}][relationship]" class="input text-sm w-full">
                @foreach($relationships as $rel)
                    <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <input type="date" name="family[\${familyIndex}][birthdate]" class="input text-sm w-full">
        </div>

        <div class="flex justify-center">
            <button type="button"
                onclick="removeRow(this)"
                class="text-red-500 hover:text-red-700">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        </div>

    </div>
    `;

    document.getElementById('familyContainer').insertAdjacentHTML('beforeend', html);
    familyIndex++;
}

function removeRow(btn) {
    btn.closest('.family-row').remove();
}
</script>

<!-- STYLE -->
<style>
.step-card{
    background:#f1f5f9;
    padding:16px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    color:#64748b;
    transition:.2s;
    min-height:75px;
}
.step-card:hover{
    background:#e2e8f0;
}
.step-card.active{
    background:#234E70;
    color:white;
    border:none;
}
.label{
    font-size:14px;
    color:#4b5563;
    display:block;
    margin-bottom:6px;
}
.input{
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:10px 12px;
    outline:none;
}
.input-select{
    appearance:none;
    background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding-right:44px;
}
.input:focus{
    border-color:#234E70;
    box-shadow:0 0 0 2px rgba(35,78,112,.12);
}
.input:disabled{
    color:#94a3b8;
    background:#f8fafc;
    cursor:not-allowed;
}
.select-shell{
    position:relative;
}
.select-shell::after{
    content:'';
    position:absolute;
    right:16px;
    top:50%;
    width:10px;
    height:10px;
    border-right:2px solid #64748b;
    border-bottom:2px solid #64748b;
    transform:translateY(-70%) rotate(45deg);
    pointer-events:none;
}
</style>

@endsection
