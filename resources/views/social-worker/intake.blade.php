@extends('layouts.app')

@section('content')

@php
    $assistanceCatalog = $assistanceTypes->map(fn ($type) => [
        'id' => $type->id,
        'name' => $type->name,
        'subtypes' => $type->subtypes->map(fn ($subtype) => [
            'id' => $subtype->id,
            'name' => $subtype->name,
            'details' => $subtype->details->map(fn ($detail) => [
                'id' => $detail->id,
                'name' => $detail->name,
            ])->values(),
        ])->values(),
    ])->values();

    $modeOptions = $modesOfAssistance->map(fn ($mode) => [
        'id' => $mode->id,
        'name' => $mode->name,
    ])->values();

    $savedRecommendations = old('recommendations')
        ? collect(old('recommendations'))->values()
        : $application->assistanceRecommendations->map(fn ($recommendation) => [
            'assistance_type_id' => $recommendation->assistance_type_id,
            'assistance_subtype_id' => $recommendation->assistance_subtype_id,
            'assistance_detail_id' => $recommendation->assistance_detail_id,
            'mode_of_assistance_id' => $recommendation->mode_of_assistance_id,
            'final_amount' => $recommendation->final_amount,
            'frequency_override_reason' => $recommendation->frequency_override_reason,
            'frequency_status' => $recommendation->frequency_status,
            'frequency_message' => $recommendation->frequency_message,
            'allows_override' => (bool) $recommendation->frequencyRule?->allows_exception_request,
            'notes' => $recommendation->notes,
        ])->values();
@endphp

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div>
        <a href="{{ route('socialworker.applications') }}"
           class="text-sm text-gray-500 hover:text-[#234E70]">
            ← Back to Applications
        </a>

        <h1 class="text-3xl font-bold text-[#234E70] mt-2">
            Intake Assessment
        </h1>

        <p class="text-gray-500">
            Reference: {{ $application->reference_no }}
        </p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#234E70]/70">Case Snapshot</p>
                <h2 class="text-2xl font-bold text-[#234E70] mt-1">Client, Beneficiary, and Assessment</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto lg:min-w-[460px]">
                <button type="button"
                        class="summary-tab is-active"
                        data-tab-target="client-summary">
                    <span class="summary-tab__eyebrow">Profile</span>
                    <span class="summary-tab__title">Client Information</span>
                </button>

                @if($application->beneficiary)
                <button type="button"
                        class="summary-tab"
                        data-tab-target="beneficiary-summary">
                    <span class="summary-tab__eyebrow">Linked Person</span>
                    <span class="summary-tab__title">Beneficiary</span>
                </button>
                @endif

                <button type="button"
                        class="summary-tab {{ $application->beneficiary ? '' : 'sm:col-span-2' }}"
                        data-tab-target="assessment-summary">
                    <span class="summary-tab__eyebrow">Review</span>
                    <span class="summary-tab__title">Assessment Details</span>
                </button>
            </div>
        </div>

        <div id="client-summary" class="summary-panel is-active">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                <div class="summary-stat">
                    <span class="summary-label">Name</span>
                    <p class="summary-value">{{ $application->client->last_name }}, {{ $application->client->first_name }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Sex</span>
                    <p class="summary-value">{{ $application->client->sex ?: '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Birthdate</span>
                    <p class="summary-value">{{ $application->client->birthdate ?: '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Civil Status</span>
                    <p class="summary-value">{{ $application->client->civil_status ?: '-' }}</p>
                </div>
            </div>

            <div class="summary-highlight mt-4">
                <span class="summary-label">Address</span>
                <p class="summary-value mt-2">{{ $application->client->full_address ?: '-' }}</p>
            </div>
        </div>

        @if($application->beneficiary)
        <div id="beneficiary-summary" class="summary-panel hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                <div class="summary-stat">
                    <span class="summary-label">Name</span>
                    <p class="summary-value">{{ $application->beneficiary->last_name }}, {{ $application->beneficiary->first_name }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Sex</span>
                    <p class="summary-value">{{ $application->beneficiary->sex ?: '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Birthdate</span>
                    <p class="summary-value">{{ $application->beneficiary->birthdate ?: '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Contact</span>
                    <p class="summary-value">{{ $application->beneficiary->contact_number ?: '-' }}</p>
                </div>
            </div>

            <div class="summary-highlight mt-4">
                <span class="summary-label">Address</span>
                <p class="summary-value mt-2">{{ $application->beneficiary->full_address ?: '-' }}</p>
            </div>
        </div>
        @endif

        <div id="assessment-summary" class="summary-panel hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                <div class="summary-stat">
                    <span class="summary-label">Assistance Type</span>
                    <p class="summary-value">{{ $application->assistanceType->name ?? '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Specific Assistance</span>
                    <p class="summary-value">{{ $application->assistanceSubtype->name ?? '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Assistance Detail</span>
                    <p class="summary-value">{{ $application->assistanceDetail->name ?? '-' }}</p>
                </div>

                <div class="summary-stat">
                    <span class="summary-label">Mode of Assistance</span>
                    <p class="summary-value">{{ $application->modeOfAssistance->name ?? $application->mode_of_assistance ?: '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm mt-4">
                <div class="summary-stat">
                    <span class="summary-label">Schedule</span>
                    <p class="summary-value">{{ $application->schedule_date ? \Carbon\Carbon::parse($application->schedule_date)->format('M d, Y h:i A') : '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4 text-sm">
                <div class="summary-highlight">
                    <span class="summary-label">Assessment Notes</span>
                    <p class="summary-value mt-2 whitespace-pre-line">{{ $application->notes ?: '-' }}</p>
                </div>

                <div class="summary-highlight">
                    <span class="summary-label">Meeting Link</span>
                    @if($application->meeting_link)
                        <a href="{{ $application->meeting_link }}"
                           target="_blank"
                           class="summary-link mt-2 inline-block break-all">
                            {{ $application->meeting_link }}
                        </a>
                    @else
                        <p class="summary-value mt-2">-</p>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <span class="summary-label">Document Remarks</span>

                <div class="mt-3 grid gap-3">
                    @forelse($application->documents as $document)
                        <div class="summary-highlight">
                            <p class="summary-value">{{ $document->file_name ?? $document->filename }}</p>
                            <p class="mt-2 text-sm text-gray-600">{{ $document->remarks ?: 'No remarks added.' }}</p>
                        </div>
                    @empty
                        <div class="summary-highlight">
                            <p class="text-sm text-gray-600">No documents uploaded.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <form id="intakeForm"
          method="POST"
          action="{{ route('socialworker.intake.save', $application->id) }}">

        @csrf

        <!-- STEP CARDS -->
        <div class="grid grid-cols-3 gap-4">

            <div id="step-indicator-1"
                 onclick="goToStep(1)"
                 class="step-card active cursor-pointer">
                <p class="text-xs uppercase">Step 1</p>
                <p class="font-semibold">Financial Status</p>
            </div>

            <div id="step-indicator-2"
                 onclick="goToStep(2)"
                 class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 2</p>
                <p class="font-semibold">Crisis & Risk</p>
            </div>

            <div id="step-indicator-3"
                 onclick="goToStep(3)"
                 class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 3</p>
                <p class="font-semibold">Recommendations</p>
            </div>

        </div>

        <!-- ================= STEP 1 ================= -->
        <div id="step-1" class="step-content bg-white p-6 rounded-xl shadow space-y-6">

            <h2 class="text-lg font-bold text-[#234E70]">
                Financial Capacity
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Combined Monthly Income</label>
                    <input type="number"
                           step="0.01"
                           name="monthly_income"
                           class="input w-full"
                           value="{{ $application->monthly_income }}">
                </div>

                <div>
                    <label class="label">Monthly Expenses</label>
                    <input type="number"
                           step="0.01"
                           name="monthly_expenses"
                           class="input w-full"
                           value="{{ $application->monthly_expenses }}">
                </div>

                <div>
                    <label class="label">Household Members</label>
                    <input type="number"
                           name="household_members"
                           class="input w-full"
                           value="{{ $application->household_members }}">
                </div>

                <div>
                    <label class="label">Working Members</label>
                    <input type="number"
                           name="working_members"
                           class="input w-full"
                           value="{{ $application->working_members }}">
                </div>

                <div class="col-span-2">
                    <label class="label">Savings Available</label>
                    <input type="number"
                           step="0.01"
                           name="savings"
                           class="input w-full"
                           value="{{ $application->savings }}">
                </div>

            </div>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <h2 class="text-lg font-bold text-[#234E70]">
                Crisis Severity & Risk Factors
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Type of Crisis</label>
                    <select name="crisis_type" class="input w-full">
                        <option value="">Select</option>
                        <option value="Hospitalization" @selected($application->crisis_type === 'Hospitalization')>Hospitalization</option>
                        <option value="Death" @selected($application->crisis_type === 'Death')>Death in Family</option>
                        <option value="Disaster" @selected($application->crisis_type === 'Disaster')>Fire/Flood/Disaster</option>
                        <option value="Loss of Livelihood" @selected($application->crisis_type === 'Loss of Livelihood')>Loss of Livelihood</option>
                        <option value="Food Need" @selected($application->crisis_type === 'Food Need')>Food Need</option>
                        <option value="Education Need" @selected($application->crisis_type === 'Education Need')>Education Need</option>
                    </select>
                </div>

                <div>
                    <label class="label">Urgency Level</label>
                    <select name="urgency_level" class="input w-full">
                        <option value="">Select</option>
                        <option value="Low" @selected($application->urgency_level === 'Low')>Low</option>
                        <option value="Medium" @selected($application->urgency_level === 'Medium')>Medium</option>
                        <option value="High" @selected($application->urgency_level === 'High')>High</option>
                        <option value="Critical" @selected($application->urgency_level === 'Critical')>Critical</option>
                    </select>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-4">

                <label class="check-box">
                    <input type="checkbox" name="has_elderly" value="1" @checked($application->has_elderly)>
                    Elderly in Household
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_child" value="1" @checked($application->has_child)>
                    Child in Need
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_pwd" value="1" @checked($application->has_pwd)>
                    Person with Disability
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_pregnant" value="1" @checked($application->has_pregnant)>
                    Pregnant Household Member
                </label>

                <label class="check-box">
                    <input type="checkbox" name="earner_unable_to_work" value="1" @checked($application->earner_unable_to_work)>
                    Main Earner Unable to Work
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_philhealth" value="1" @checked($application->has_philhealth)>
                    Has PhilHealth / Health Card
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_family_support" value="1" @checked($application->has_family_support)>
                    Has Family Support
                </label>

            </div>

        </div>

        <!-- ================= STEP 3 ================= -->
        <div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <div>
                <div>
                    <h2 class="text-lg font-bold text-[#234E70]">
                        Recommendations
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Save the primary assistance amount, then add other identified needs below.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-gray-500">
                    Generate an AI recommendation from the intake data, then adjust the final amount if needed.
                </p>

                <button type="button"
                        id="generateRecommendationBtn"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    Generate AI Recommendation
                </button>
            </div>

            <div hidden id="recommendationStatus"
                 class="text-sm rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600">
                @if($application->ai_recommendation_summary)
                    Last saved recommendation loaded.
                @else
                    No AI recommendation generated yet.
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Primary Recommended Amount</label>
                    <input type="number"
                        id="recommended_amount"
                        name="recommended_amount"
                        class="input w-full bg-gray-100"
                        value="{{ $application->recommended_amount }}"
                        readonly>
                </div>

                <div>
                    <label class="label">Primary Final Amount</label>
                    <input type="number"
                        step="0.01"
                        name="final_amount"
                        class="input w-full"
                        value="{{ $application->final_amount }}">
                </div>

            </div>

            <div class="recommendation-panel">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-[#234E70]">Additional Assistance Recommendations</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Add cash relief, material assistance, psychosocial support, or another need identified during intake.
                        </p>
                    </div>

                    <button type="button"
                            id="addAssistanceRecommendationBtn"
                            class="px-4 py-2 rounded-lg bg-[#234E70] text-white hover:bg-[#18384f]">
                        Add Assistance
                    </button>
                </div>

                <div id="additionalRecommendations" class="mt-4 space-y-4"></div>
            </div>

            <div hidden>
                <label class="label">AI Recommendation Summary</label>
                <textarea id="ai_recommendation_summary"
                        class="input w-full h-28 bg-gray-50"
                        readonly>{{ $application->ai_recommendation_summary }}</textarea>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div hidden>
                    <label class="label">Confidence</label>
                    <input type="text"
                        id="ai_recommendation_confidence"
                        class="input w-full bg-gray-50"
                        value="{{ $application->ai_recommendation_confidence ? $application->ai_recommendation_confidence.'%' : '' }}"
                        readonly>
                </div>

                <div hidden>
                    <label class="label">Source</label>
                    <input type="text"
                        id="ai_recommendation_source"
                        class="input w-full bg-gray-50"
                        value="{{ $application->ai_recommendation_source }}"
                        readonly>
                </div>

                <div hidden>
                    <label class="label">Model</label>
                    <input type="text"
                        id="ai_recommendation_model"
                        class="input w-full bg-gray-50"
                        value="{{ $application->ai_recommendation_model }}"
                        readonly>
                </div>
            </div>

            <div>
                <label class="label">Problem Statement</label>
                <textarea name="problem_statement"
                        class="input w-full h-28">{{ $application->problem_statement }}</textarea>
            </div>

            <div>
                <label class="label">Social Worker Assessment</label>
                <textarea name="social_worker_assessment"
                        class="input w-full h-32">{{ $application->social_worker_assessment }}</textarea>
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
                    class="px-6 py-3 bg-[#234E70] text-white rounded-lg hover:bg-[#18384f]">
                Next →
            </button>

        </div>

    </form>

</main>

<script>
let currentStep = 1;
const totalSteps = 3;
const intakeForm = document.getElementById('intakeForm');
const recommendationStatus = document.getElementById('recommendationStatus');
const generateRecommendationBtn = document.getElementById('generateRecommendationBtn');
const recommendationUrl = @json(route('socialworker.recommendation.generate', $application->id));
const frequencyCheckUrl = @json(route('socialworker.assistance-frequency.check', $application->id));
const assistanceCatalog = @json($assistanceCatalog);
const modeOptions = @json($modeOptions);
let additionalRecommendations = @json($savedRecommendations);

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
        currentStep === totalSteps ? 'Save Intake' : 'Next →';
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
        intakeForm.submit();
    }
});

document.getElementById('backBtn').addEventListener('click', function () {
    currentStep--;
    updateSteps();
});

updateSteps();

function setRecommendationFields(data) {
    document.getElementById('recommended_amount').value = data.recommended_amount ?? '';
    document.getElementById('ai_recommendation_summary').value = data.summary ?? '';
    document.getElementById('ai_recommendation_confidence').value =
        data.confidence !== undefined && data.confidence !== null ? `${data.confidence}%` : '';
    document.getElementById('ai_recommendation_source').value = data.source ?? '';
    document.getElementById('ai_recommendation_model').value = data.model ?? '';
}

async function generateRecommendation() {
    const formData = new FormData(intakeForm);

    recommendationStatus.textContent = 'Generating recommendation...';
    generateRecommendationBtn.disabled = true;
    generateRecommendationBtn.classList.add('opacity-70', 'cursor-not-allowed');

    try {
        const response = await fetch(recommendationUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': formData.get('_token'),
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            const message = data.message || 'Unable to generate recommendation.';
            recommendationStatus.textContent = message;
            return;
        }

        setRecommendationFields(data);
        recommendationStatus.textContent =
            `Recommendation ready (${data.source || 'ai'}${data.confidence ? `, ${data.confidence}% confidence` : ''}).`;
    } catch (error) {
        recommendationStatus.textContent = 'Unable to generate recommendation right now.';
    } finally {
        generateRecommendationBtn.disabled = false;
        generateRecommendationBtn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
}

generateRecommendationBtn.addEventListener('click', generateRecommendation);

const additionalRecommendationsContainer = document.getElementById('additionalRecommendations');
const addAssistanceRecommendationBtn = document.getElementById('addAssistanceRecommendationBtn');

function optionList(options, selectedValue, placeholder = 'Select') {
    const selected = String(selectedValue || '');
    return [
        `<option value="">${placeholder}</option>`,
        ...options.map((option) => {
            const isSelected = String(option.id) === selected ? 'selected' : '';
            return `<option value="${option.id}" ${isSelected}>${option.name}</option>`;
        }),
    ].join('');
}

function selectedType(row) {
    return assistanceCatalog.find((type) => String(type.id) === String(row.assistance_type_id));
}

function selectedSubtype(row) {
    return selectedType(row)?.subtypes.find((subtype) => String(subtype.id) === String(row.assistance_subtype_id));
}

function blankRecommendation() {
    return {
        assistance_type_id: '',
        assistance_subtype_id: '',
        assistance_detail_id: '',
        mode_of_assistance_id: '',
        final_amount: '',
        frequency_override_reason: '',
        frequency_status: '',
        frequency_message: '',
        notes: '',
    };
}

function renderAdditionalRecommendations() {
    if (!additionalRecommendations.length) {
        additionalRecommendationsContainer.innerHTML = `
            <div class="empty-state">
                <p class="font-semibold text-gray-700">No additional assistance added.</p>
                <p class="text-sm text-gray-500 mt-1">Use this when assessment identifies a second eligible need beyond the primary application assistance.</p>
            </div>
        `;
        return;
    }

    additionalRecommendationsContainer.innerHTML = additionalRecommendations.map((row, index) => {
        const type = selectedType(row);
        const subtype = selectedSubtype(row);
        const statusClass = row.frequency_status === 'blocked'
            ? 'status-badge status-badge--blocked'
            : row.frequency_status === 'eligible'
                ? 'status-badge status-badge--eligible'
                : 'status-badge';
        const canOverride = row.frequency_status === 'blocked' && row.allows_override;
        const blockedWithoutOverride = row.frequency_status === 'blocked' && !row.allows_override;

        return `
            <div class="additional-recommendation" data-index="${index}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-[#234E70]">Additional Assistance ${index + 1}</h4>
                        <p class="text-sm text-gray-500">Frequency is checked before this row is saved.</p>
                    </div>
                    <button type="button" class="text-sm font-semibold text-red-600" data-remove-recommendation="${index}">Remove</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label">Assistance Type</label>
                        <select name="recommendations[${index}][assistance_type_id]" class="input" data-recommendation-field="assistance_type_id">
                            ${optionList(assistanceCatalog, row.assistance_type_id)}
                        </select>
                    </div>
                    <div>
                        <label class="label">Specific Assistance</label>
                        <select name="recommendations[${index}][assistance_subtype_id]" class="input" data-recommendation-field="assistance_subtype_id">
                            ${optionList(type?.subtypes || [], row.assistance_subtype_id)}
                        </select>
                    </div>
                    <div>
                        <label class="label">Assistance Detail</label>
                        <select name="recommendations[${index}][assistance_detail_id]" class="input" data-recommendation-field="assistance_detail_id">
                            ${optionList(subtype?.details || [], row.assistance_detail_id, 'None')}
                        </select>
                    </div>
                    <div>
                        <label class="label">Mode</label>
                        <select name="recommendations[${index}][mode_of_assistance_id]" class="input" data-recommendation-field="mode_of_assistance_id">
                            ${optionList(modeOptions, row.mode_of_assistance_id)}
                        </select>
                    </div>
                    <div>
                        <label class="label">Final Amount</label>
                        <input type="number" step="0.01" name="recommendations[${index}][final_amount]" class="input" value="${row.final_amount || ''}" data-recommendation-field="final_amount">
                    </div>
                </div>

                ${canOverride ? `
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <label class="label">Override Reason</label>
                        <textarea name="recommendations[${index}][frequency_override_reason]" class="input h-24 bg-white" data-recommendation-field="frequency_override_reason" placeholder="Explain why this blocked assistance should still be recommended.">${row.frequency_override_reason || ''}</textarea>
                    </div>
                ` : ''}

                ${blockedWithoutOverride ? `
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        This assistance is blocked by the frequency rule and this rule does not allow override.
                    </div>
                ` : ''}

                <div class="mt-4">
                    <label class="label">Recommendation Notes</label>
                    <textarea name="recommendations[${index}][notes]" class="input h-20" data-recommendation-field="notes">${row.notes || ''}</textarea>
                </div>

                <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="${statusClass}">
                        ${row.frequency_status ? row.frequency_status.replace('_', ' ') : 'not checked'}
                    </div>
                    <button type="button" class="px-4 py-2 rounded-lg border border-[#234E70] text-[#234E70] hover:bg-[#234E70] hover:text-white" data-check-frequency="${index}">
                        Check Frequency
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-600">${row.frequency_message || 'Choose an assistance and run the frequency check.'}</p>
            </div>
        `;
    }).join('');
}

function updateRecommendationRow(index, field, value) {
    additionalRecommendations[index][field] = value;

    if (field === 'assistance_type_id') {
        additionalRecommendations[index].assistance_subtype_id = '';
        additionalRecommendations[index].assistance_detail_id = '';
        additionalRecommendations[index].frequency_status = '';
        additionalRecommendations[index].frequency_message = '';
        renderAdditionalRecommendations();
    }

    if (field === 'assistance_subtype_id') {
        additionalRecommendations[index].assistance_detail_id = '';
        additionalRecommendations[index].frequency_status = '';
        additionalRecommendations[index].frequency_message = '';
        renderAdditionalRecommendations();
    }
}

async function checkRecommendationFrequency(index) {
    const row = additionalRecommendations[index];

    if (!row.assistance_type_id || !row.assistance_subtype_id) {
        row.frequency_status = 'missing';
        row.frequency_message = 'Select an assistance type and specific assistance first.';
        renderAdditionalRecommendations();
        return;
    }

    row.frequency_status = 'checking';
    row.frequency_message = 'Checking frequency rules...';
    renderAdditionalRecommendations();

    const formData = new FormData();
    formData.append('_token', intakeForm.querySelector('input[name="_token"]').value);
    ['assistance_type_id', 'assistance_subtype_id', 'assistance_detail_id', 'frequency_case_key', 'frequency_override_reason'].forEach((field) => {
        formData.append(field, row[field] || '');
    });

    try {
        const response = await fetch(frequencyCheckUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData,
        });
        const data = await response.json();

        row.frequency_status = data.status || 'review_required';
        row.frequency_message = data.message || 'Frequency check completed.';
        row.allows_override = Boolean(data.rule?.allows_exception_request);
    } catch (error) {
        row.frequency_status = 'review_required';
        row.frequency_message = 'Unable to check frequency right now. The server will check again when saving.';
        row.allows_override = false;
    }

    renderAdditionalRecommendations();
}

addAssistanceRecommendationBtn.addEventListener('click', function () {
    additionalRecommendations.push(blankRecommendation());
    renderAdditionalRecommendations();
});

additionalRecommendationsContainer.addEventListener('input', function (event) {
    const rowElement = event.target.closest('[data-index]');
    const field = event.target.dataset.recommendationField;

    if (!rowElement || !field) {
        return;
    }

    updateRecommendationRow(Number(rowElement.dataset.index), field, event.target.value);
});

additionalRecommendationsContainer.addEventListener('change', function (event) {
    const rowElement = event.target.closest('[data-index]');
    const field = event.target.dataset.recommendationField;

    if (!rowElement || !field) {
        return;
    }

    updateRecommendationRow(Number(rowElement.dataset.index), field, event.target.value);
});

additionalRecommendationsContainer.addEventListener('click', function (event) {
    const removeIndex = event.target.dataset.removeRecommendation;
    const checkIndex = event.target.dataset.checkFrequency;

    if (removeIndex !== undefined) {
        additionalRecommendations.splice(Number(removeIndex), 1);
        renderAdditionalRecommendations();
    }

    if (checkIndex !== undefined) {
        checkRecommendationFrequency(Number(checkIndex));
    }
});

renderAdditionalRecommendations();

const summaryTabs = document.querySelectorAll('.summary-tab');
const summaryPanels = document.querySelectorAll('.summary-panel');

summaryTabs.forEach((tab) => {
    tab.addEventListener('click', function () {
        const targetId = this.dataset.tabTarget;

        summaryTabs.forEach((item) => item.classList.remove('is-active'));
        summaryPanels.forEach((panel) => {
            panel.classList.add('hidden');
            panel.classList.remove('is-active');
        });

        this.classList.add('is-active');
        document.getElementById(targetId).classList.remove('hidden');
        document.getElementById(targetId).classList.add('is-active');
    });
});
</script>
<style>
.summary-tab{
    text-align:left;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid #dbe7ef;
    background:linear-gradient(180deg,#f8fbfd 0%,#eef4f8 100%);
    transition:.2s;
}
.summary-tab:hover{
    border-color:#9db7ca;
    transform:translateY(-1px);
}
.summary-tab.is-active{
    background:linear-gradient(135deg,#234E70 0%,#2f6a91 100%);
    border-color:#234E70;
    color:#fff;
    box-shadow:0 14px 30px rgba(35,78,112,.18);
}
.summary-tab__eyebrow{
    display:block;
    font-size:11px;
    letter-spacing:.12em;
    text-transform:uppercase;
    opacity:.75;
}
.summary-tab__title{
    display:block;
    margin-top:6px;
    font-size:15px;
    font-weight:700;
}
.summary-panel{
    border-top:1px solid #e5e7eb;
    padding-top:24px;
}
.summary-stat,
.summary-highlight{
    border:1px solid #e5e7eb;
    border-radius:18px;
    background:#f8fafc;
    padding:18px;
}
.summary-highlight{
    background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
}
.summary-label{
    font-size:11px;
    font-weight:700;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:#6b7280;
}
.summary-value{
    margin-top:8px;
    font-size:15px;
    font-weight:600;
    color:#1f2937;
}
.summary-link{
    color:#234E70;
    font-weight:600;
}
.step-card{
    background:#f1f5f9;
    padding:16px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    color:#64748b;
    min-height:75px;
}
.step-card.active{
    background:#234E70;
    color:white;
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
    width:100%;
}
.check-box{
    display:flex;
    align-items:center;
    gap:10px;
    background:#f9fafb;
    padding:12px;
    border-radius:10px;
    border:1px solid #e5e7eb;
}
.recommendation-panel,
.additional-recommendation,
.empty-state{
    border:1px solid #e5e7eb;
    border-radius:16px;
    background:#f8fafc;
    padding:18px;
}
.additional-recommendation{
    background:#fff;
}
.status-badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    border:1px solid #cbd5e1;
    background:#f1f5f9;
    padding:6px 12px;
    font-size:12px;
    font-weight:700;
    color:#475569;
    text-transform:uppercase;
}
.status-badge--eligible{
    border-color:#bbf7d0;
    background:#dcfce7;
    color:#166534;
}
.status-badge--blocked{
    border-color:#fecdd3;
    background:#ffe4e6;
    color:#be123c;
}
</style>

@endsection
