@extends('layouts.app')

@section('content')

@php
    $assistanceCatalog = $assistanceTypes->map(fn ($type) => [
        'id' => $type->id,
        'name' => $type->name,
        'is_non_monetary' => str_contains(strtolower($type->name), 'psychosocial')
            || str_contains(strtolower($type->name), 'referral'),
        'subtypes' => str_contains(strtolower($type->name), 'referral')
            ? []
            : $type->subtypes->map(fn ($subtype) => [
            'id' => $subtype->id,
            'name' => $subtype->name,
            'is_non_monetary' => str_contains(strtolower($type->name), 'psychosocial')
                || str_contains(strtolower($type->name), 'referral')
                || str_contains(strtolower($subtype->name), 'psychosocial')
                || str_contains(strtolower($subtype->name), 'referral'),
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

    $referralInstitutionOptions = $referralInstitutions->map(fn ($institution) => [
        'id' => $institution->id,
        'name' => $institution->name,
        'addressee' => $institution->addressee,
        'address' => $institution->address,
        'email' => $institution->email,
        'contact_number' => $institution->contact_number,
    ])->values();

    $savedRecommendations = old('recommendations')
        ? collect(old('recommendations'))->values()
        : $application->assistanceRecommendations->map(fn ($recommendation) => [
            'assistance_type_id' => $recommendation->assistance_type_id,
            'assistance_subtype_id' => $recommendation->assistance_subtype_id,
            'assistance_detail_id' => $recommendation->assistance_detail_id,
            'mode_of_assistance_id' => $recommendation->mode_of_assistance_id,
            'referral_institution_id' => $recommendation->referral_institution_id,
            'final_amount' => $recommendation->final_amount,
            'frequency_override_reason' => $recommendation->frequency_override_reason,
            'frequency_status' => $recommendation->frequency_status,
            'frequency_message' => $recommendation->frequency_message,
            'notes' => $recommendation->notes,
        ])->values();

    $recentCrisisTypes = old('recent_crisis_types', $application->recent_crisis_types ?? []);
    $supportSystems = old('support_systems', $application->support_systems ?? []);
    $externalResources = old('external_resources', $application->external_resources ?? []);
    $selfHelpEfforts = old('self_help_efforts', $application->self_help_efforts ?? []);
    $clientSectors = old('client_sectors', $application->client_sectors ?? ($application->client_sector ? [$application->client_sector] : []));
    $clientSubCategories = old('client_sub_categories', $application->client_sub_categories ?? ($application->client_sub_category ? [$application->client_sub_category] : []));
    $disabilityTypes = old('disability_types', $application->disability_types ?? ($application->disability_type ? [$application->disability_type] : []));
    $incomeSources = collect(old('income_sources', $application->income_sources ?? []));
    $incomeSourceLabels = [
        'Salaries/Wages from Employment',
        'Entrepreneurial income/profits',
        'Cash assistance from domestic source',
        'Cash assistance from abroad',
        'Transfers from the government (e.g. 4Ps)',
        'Pension',
        'Other income',
    ];
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

        <a href="{{ route('socialworker.general-intake-sheet', $application->id) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="mt-3 inline-flex items-center justify-center rounded-lg bg-[#234E70] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#18384f]">
            Print General Intake Sheet
        </a>
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

            <div>
                <h2 class="text-lg font-bold text-[#234E70]">General Intake Sheet</h2>
                <p class="text-sm text-gray-500 mt-1">Client visit details, financial resources, and budget information.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Client Type</label>
                    <select name="gis_client_type" class="input w-full">
                        <option value="">Select</option>
                        <option value="New" @selected(old('gis_client_type', $application->gis_client_type) === 'New')>New Walk-in</option>
                        <option value="Returning" @selected(old('gis_client_type', $application->gis_client_type) === 'Returning')>Returning</option>
                        <option value="Referral" @selected(old('gis_client_type', $application->gis_client_type) === 'Referral')>Referral</option>
                    </select>
                </div>

                <div>
                    <label class="label">GIS Service Point</label>
                    <select name="gis_visit_type" class="input w-full">
                        <option value="">Select</option>
                        @foreach(['AICS Onsite', 'AKAP', 'Malasakit Center', 'Offsite', 'Others'] as $visitType)
                            <option value="{{ $visitType }}" @selected(old('gis_visit_type', $application->gis_visit_type) === $visitType)>{{ $visitType }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Diagnosis / Cause of Death</label>
                    <input type="text"
                           name="diagnosis_or_cause_of_death"
                           class="input w-full"
                           value="{{ old('diagnosis_or_cause_of_death', $application->diagnosis_or_cause_of_death) }}">
                </div>

                <div>
                    <label class="label">Amount Needed</label>
                    <input type="number"
                           step="0.01"
                           name="amount_needed"
                           class="input w-full"
                           value="{{ old('amount_needed', $application->amount_needed) }}">
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">I. Income and Financial Resources</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-sm font-semibold text-gray-700">Occupation/s of Family Member</p>
                    </div>

                    <div>
                        <label class="label">Employed Family Members</label>
                        <input type="number"
                               name="working_members"
                               class="input w-full"
                               value="{{ old('working_members', $application->working_members) }}">
                    </div>

                    <div>
                        <label class="label">Seasonal Employee Members</label>
                        <input type="number"
                               name="seasonal_worker_members"
                               class="input w-full"
                               value="{{ old('seasonal_worker_members', $application->seasonal_worker_members) }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Combined Monthly Income</label>
                        <input type="number"
                               step="0.01"
                               name="monthly_income"
                               class="input w-full"
                               value="{{ old('monthly_income', $application->monthly_income) }}">
                    </div>

                    <div>
                        <label class="label">Insurance Coverage</label>
                        <label class="check-box">
                            <input type="checkbox"
                                   name="has_insurance_coverage"
                                   value="1"
                                   @checked(old('has_insurance_coverage', $application->has_insurance_coverage))>
                            Yes
                        </label>
                    </div>

                    <div>
                        <label class="label">Savings</label>
                        <label class="check-box">
                            <input type="checkbox"
                                   name="has_savings"
                                   value="1"
                                   @checked(old('has_savings', $application->has_savings))>
                            Yes
                        </label>
                    </div>
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">II. Budget and Expenses</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Monthly Expenses of the Family</label>
                        <input type="number"
                               step="0.01"
                               name="monthly_expenses"
                               class="input w-full"
                               value="{{ old('monthly_expenses', $application->monthly_expenses) }}">
                    </div>

                    <div>
                        <label class="label">Availability of Emergency Fund</label>
                        <label class="check-box">
                            <input type="hidden" name="emergency_fund" value="No">
                            <input type="checkbox"
                                   name="emergency_fund"
                                   value="Yes"
                                   @checked(old('emergency_fund', $application->emergency_fund) === 'Yes')>
                            Yes, emergency fund is available
                        </label>
                    </div>
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">Income in the Past 6 Months</h3>

                <div class="grid gap-3">
                    @foreach($incomeSourceLabels as $index => $label)
                        @php
                            $incomeRow = $incomeSources->firstWhere('source', $label) ?? [];
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_220px] gap-3">
                            <input type="hidden" name="income_sources[{{ $index }}][source]" value="{{ $label }}">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ $label }}</div>
                            <input type="number"
                                   step="0.01"
                                   name="income_sources[{{ $index }}][amount]"
                                   class="input w-full"
                                   value="{{ $incomeRow['amount'] ?? '' }}"
                                   placeholder="Amount">
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <label class="label">Total Income in the Past 6 Months</label>
                    <input type="number"
                           step="0.01"
                           name="total_income_past_six_months"
                           class="input w-full"
                           value="{{ old('total_income_past_six_months', $application->total_income_past_six_months) }}">
                </div>
            </div>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <div>
                <h2 class="text-lg font-bold text-[#234E70]">GIS Crisis, Support, and Sector Assessment</h2>
                <p class="text-sm text-gray-500 mt-1">Sections III to IX of the General Intake Sheet.</p>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">III. Severity of the Crisis</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">How long does the patient suffer from the disease?</label>
                        <select name="disease_duration" class="input w-full">
                            <option value="">Select</option>
                            @foreach(['Recently diagnosed (3 months and below)', '3 months to a year', 'Chronic or lifelong', 'Not applicable'] as $duration)
                                <option value="{{ $duration }}" @selected(old('disease_duration', $application->disease_duration) === $duration)>{{ $duration }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Crisis Experienced in the Past 3 Months</label>
                        <select name="experienced_recent_crisis" class="input w-full">
                            <option value="">Select</option>
                            <option value="1" @selected((string) old('experienced_recent_crisis', $application->experienced_recent_crisis) === '1')>Yes</option>
                            <option value="0" @selected((string) old('experienced_recent_crisis', $application->experienced_recent_crisis) === '0')>No</option>
                        </select>
                    </div>

                </div>

                <p class="mt-4 text-sm font-semibold text-gray-700">
                    If yes, which among the following crises did the family experience in the past three (3) months (check all that apply):
                </p>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(['Hospitalization', 'Death of a family member', 'Catastrophic Event (fire, earthquake, flooding, etc.)', 'Disablement', 'Inability to secure stable employment', 'Loss of Livelihood', 'Others'] as $crisisOption)
                        <label class="check-box">
                            <input type="checkbox" name="recent_crisis_types[]" value="{{ $crisisOption }}" @checked(in_array($crisisOption, $recentCrisisTypes, true))>
                            {{ $crisisOption }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">IV. Availability of Support Systems</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(['Family', 'Friend/s', 'Employed Relatives', 'Employer', 'Seasonal Employee', 'Church/Community Organization', 'Not applicable'] as $supportOption)
                        <label class="check-box">
                            <input type="checkbox" name="support_systems[]" value="{{ $supportOption }}" @checked(in_array($supportOption, $supportSystems, true))>
                            {{ $supportOption }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">V. External Resources Tapped by the Family</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(['Health Card', 'Guarantee Letter from other agencies', 'MSS Discount', 'Senior Citizen Discount', 'PWD Discount', 'PhilHealth', 'Others'] as $resourceOption)
                        <label class="check-box">
                            <input type="checkbox" name="external_resources[]" value="{{ $resourceOption }}" @checked(in_array($resourceOption, $externalResources, true))>
                            {{ $resourceOption }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">VI. Self-Help and Client Efforts</h3>

                <div class="grid grid-cols-1 gap-3">
                    @foreach(['Successfully sought employment opportunities or explored additional income sources', 'Successfully reached out to relevant organizations or agencies for financial assistance or support'] as $effortOption)
                        <label class="check-box">
                            <input type="checkbox" name="self_help_efforts[]" value="{{ $effortOption }}" @checked(in_array($effortOption, $selfHelpEfforts, true))>
                            {{ $effortOption }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">VII. Vulnerability and Risk Factors</h3>

                <div class="grid grid-cols-1 gap-3">
                    <label class="check-box">
                        <input type="checkbox"
                               name="has_vulnerable_household_member"
                               value="1"
                               @checked(old('has_vulnerable_household_member', $application->has_vulnerable_household_member))>
                        There are elderly/ Child in need/ PWD/ Pregnant in the household
                    </label>

                    <label class="check-box">
                        <input type="checkbox"
                               name="earner_unable_to_work"
                               value="1"
                               @checked(old('earner_unable_to_work', $application->earner_unable_to_work))>
                        A member is physically or mentally incapacitated to work
                    </label>

                    <label class="check-box">
                        <input type="checkbox"
                               name="has_unstable_employment"
                               value="1"
                               @checked(old('has_unstable_employment', $application->has_unstable_employment))>
                        Inability to secure stable employment
                    </label>
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">VIII. Client Sector</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Target Sector</label>
                        <div class="grid gap-2">
                            @foreach(['FHONA', 'WEDC', 'PWD', 'CNSP', 'SC', 'YNSP', 'PLHIV'] as $sector)
                                <label class="check-box">
                                    <input type="checkbox"
                                           name="client_sectors[]"
                                           value="{{ $sector }}"
                                           data-client-sector-option
                                           @checked(in_array($sector, $clientSectors, true))>
                                    {{ $sector }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="label">Specify Sub-Category</label>
                        <div class="grid gap-2">
                            @foreach([
                                ['Solo Parent', 'FHONA'],
                                ['Indigenous People', 'FHONA'],
                                ['Street Dwellers', 'FHONA'],
                                ['KIA/WIA', 'CNSP'],
                                ['4PS Beneficiary', 'FHONA'],
                                ['Stateless Person', 'FHONA'],
                                ['Asylum Seekers', 'FHONA'],
                                ['Refugees', 'FHONA'],
                                ['Recovering Person Who Used Drugs', 'YNSP'],
                                ['Minimum Wage Earner', 'FHONA'],
                                ['Earner (specify approximate monthly income)', 'FHONA'],
                                ['No Regular Income', 'FHONA'],
                                ['Others', ''],
                            ] as [$subCategory, $mappedSector])
                                <label class="check-box">
                                    <input type="checkbox"
                                           name="client_sub_categories[]"
                                           value="{{ $subCategory }}"
                                           data-client-sub-category-option
                                           data-target-sector="{{ $mappedSector }}"
                                           @checked(in_array($subCategory, $clientSubCategories, true))>
                                    {{ $subCategory }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="disabilityTypeField" class="{{ in_array('PWD', $clientSectors, true) ? '' : 'hidden' }}">
                        <label class="label">Type of Disability</label>
                        <div class="grid gap-2">
                            @foreach(['Speech Impairment', 'Mental Disability', 'Learning Disability', 'Visual Disability', 'Psychosocial Disability', 'Intellectual Disability', 'Deaf/Hard-of-Hearing', 'Physical Disability', 'Cancer', 'Rare Disease'] as $disability)
                                <label class="check-box">
                                    <input type="checkbox"
                                           name="disability_types[]"
                                           value="{{ $disability }}"
                                           data-disability-type-option
                                           @checked(in_array($disability, $disabilityTypes, true))>
                                    {{ $disability }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= STEP 3 ================= -->
        <div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <div>
                <div>
                    <h2 class="text-lg font-bold text-[#234E70]">
                        Problem, Assessment, and Recommendations
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Document the presented problem and assessment before finalizing the assistance recommendation.
                    </p>
                </div>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">IX. Problem Presented</h3>
                <textarea name="problem_statement"
                        class="input w-full h-28">{{ old('problem_statement', $application->problem_statement) }}</textarea>
            </div>

            <div class="gis-section">
                <h3 class="gis-section-title">X. Social Worker's Assessment</h3>
                <textarea name="social_worker_assessment"
                        class="input w-full h-32">{{ old('social_worker_assessment', $application->social_worker_assessment) }}</textarea>
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-gray-500">
                    Generate an AI estimate from the intake data, then add recommended assistance rows below.
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

            <div>

                <div>
                    <label class="label">AI Recommended Amount</label>
                    <input type="number"
                        id="recommended_amount"
                        name="recommended_amount"
                        class="input w-full bg-gray-100"
                        value="{{ $application->recommended_amount }}"
                        readonly>
                </div>

            </div>

            <div class="recommendation-panel">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-[#234E70]">Recommended Assistance</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Add every assistance to be recommended. The total recommended amount is computed from all monetary rows.
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
const referralInstitutionOptions = @json($referralInstitutionOptions);
let additionalRecommendations = @json($savedRecommendations);
const clientSectorOptions = document.querySelectorAll('[data-client-sector-option]');
const clientSubCategoryOptions = document.querySelectorAll('[data-client-sub-category-option]');
const disabilityTypeField = document.getElementById('disabilityTypeField');
const disabilityTypeOptions = document.querySelectorAll('[data-disability-type-option]');

function syncDisabilityTypeField() {
    if (!disabilityTypeField) {
        return;
    }

    const isPwd = Array.from(clientSectorOptions).some((option) => option.value === 'PWD' && option.checked);
    disabilityTypeField.classList.toggle('hidden', !isPwd);

    if (!isPwd) {
        disabilityTypeOptions.forEach((option) => option.checked = false);
    }
}

clientSectorOptions.forEach((option) => {
    option.addEventListener('change', syncDisabilityTypeField);
});
clientSubCategoryOptions.forEach((option) => {
    option.addEventListener('change', function () {
        const mappedSector = this.dataset.targetSector;

        if (this.checked && mappedSector) {
            const sectorOption = Array.from(clientSectorOptions).find((item) => item.value === mappedSector);
            if (sectorOption) {
                sectorOption.checked = true;
            }
        }

        syncDisabilityTypeField();
    });
});
disabilityTypeOptions.forEach((option) => {
    option.addEventListener('change', function () {
        if (this.checked) {
            const pwdOption = Array.from(clientSectorOptions).find((item) => item.value === 'PWD');
            if (pwdOption) {
                pwdOption.checked = true;
            }
        }

        syncDisabilityTypeField();
    });
});
syncDisabilityTypeField();

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

function isNonMonetaryService(row) {
    const type = selectedType(row);
    const subtype = selectedSubtype(row);

    return Boolean(type?.is_non_monetary || subtype?.is_non_monetary || isMaterialService(row));
}

function isReferralService(row) {
    const type = selectedType(row);
    const subtype = selectedSubtype(row);

    return Boolean(
        type?.name?.toLowerCase().includes('referral') ||
        subtype?.name?.toLowerCase().includes('referral')
    );
}

function isPsychosocialService(row) {
    const type = selectedType(row);
    const subtype = selectedSubtype(row);

    return Boolean(
        type?.name?.toLowerCase().includes('psychosocial') ||
        subtype?.name?.toLowerCase().includes('psychosocial')
    );
}

function isMaterialService(row) {
    const type = selectedType(row);
    const subtype = selectedSubtype(row);

    return Boolean(
        type?.name?.toLowerCase().includes('material') ||
        subtype?.name?.toLowerCase().includes('material')
    );
}

function requiresModeOfAssistance(row) {
    return !isNonMonetaryService(row) && !isMaterialService(row);
}

function selectedReferralInstitution(row) {
    return referralInstitutionOptions.find((institution) => String(institution.id) === String(row.referral_institution_id));
}

function blankRecommendation() {
    return {
        assistance_type_id: '',
        assistance_subtype_id: '',
        assistance_detail_id: '',
        mode_of_assistance_id: '',
        referral_institution_id: '',
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
                <p class="text-sm text-gray-500 mt-1">Add each assistance that will be included in the final recommendation.</p>
            </div>
        `;
        return;
    }

    additionalRecommendationsContainer.innerHTML = additionalRecommendations.map((row, index) => {
        const type = selectedType(row);
        const subtype = selectedSubtype(row);
        const nonMonetaryService = isNonMonetaryService(row);
        const referralService = isReferralService(row);
        const psychosocialService = isPsychosocialService(row);
        const showModeOfAssistance = requiresModeOfAssistance(row);
        const showAssistanceDetail = !referralService && !psychosocialService;
        const referralInstitution = selectedReferralInstitution(row);
        const statusClass = row.frequency_status === 'not_eligible'
            ? 'status-badge status-badge--not-eligible'
            : row.frequency_status === 'eligible'
                ? 'status-badge status-badge--eligible'
                : 'status-badge';
        const canOverride = row.frequency_status === 'not_eligible';
        const statusLabel = nonMonetaryService
            ? 'added service'
            : row.frequency_status === 'not_eligible'
            ? 'not eligible'
            : (row.frequency_status ? row.frequency_status.replace('_', ' ') : 'not checked');

        return `
            <div class="additional-recommendation" data-index="${index}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-[#234E70]">Recommended Assistance ${index + 1}</h4>
                        <p class="text-sm text-gray-500">${nonMonetaryService ? 'Added service. No amount or frequency check is required.' : 'Frequency is checked before this row is saved.'}</p>
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
                    ${referralService ? '' : `<div>
                        <label class="label">Specific Assistance</label>
                        <select name="recommendations[${index}][assistance_subtype_id]" class="input" data-recommendation-field="assistance_subtype_id">
                            ${optionList(type?.subtypes || [], row.assistance_subtype_id)}
                        </select>
                    </div>`}
                    ${showAssistanceDetail ? `<div>
                        <label class="label">Assistance Detail</label>
                        <select name="recommendations[${index}][assistance_detail_id]" class="input" data-recommendation-field="assistance_detail_id">
                            ${optionList(subtype?.details || [], row.assistance_detail_id, 'None')}
                        </select>
                    </div>` : ''}
                    ${showModeOfAssistance ? `<div>
                        <label class="label">Mode</label>
                        <select name="recommendations[${index}][mode_of_assistance_id]" class="input" data-recommendation-field="mode_of_assistance_id">
                            ${optionList(modeOptions, row.mode_of_assistance_id)}
                        </select>
                    </div>` : ''}
                    ${nonMonetaryService ? '' : `<div>
                        <label class="label">Recommended Amount</label>
                        <input type="number" step="0.01" name="recommendations[${index}][final_amount]" class="input" value="${row.final_amount || ''}" data-recommendation-field="final_amount">
                    </div>`}
                </div>

                ${referralService ? `
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                        <label class="label">Referral Institution / Government Agency</label>
                        <select name="recommendations[${index}][referral_institution_id]" class="input bg-white" data-recommendation-field="referral_institution_id">
                            ${optionList(referralInstitutionOptions, row.referral_institution_id, 'Select institution or agency')}
                        </select>
                        ${referralInstitution ? `
                            <div class="mt-3 grid gap-2 text-sm text-sky-900 md:grid-cols-2">
                                <p><span class="font-semibold">Addressee:</span> ${referralInstitution.addressee || '-'}</p>
                                <p><span class="font-semibold">Email:</span> ${referralInstitution.email || '-'}</p>
                                <p><span class="font-semibold">Contact:</span> ${referralInstitution.contact_number || '-'}</p>
                                <p><span class="font-semibold">Address:</span> ${referralInstitution.address || '-'}</p>
                            </div>
                        ` : `<p class="mt-2 text-sm text-sky-800">Choose the institution or agency where the client will be referred.</p>`}
                    </div>
                ` : ''}

                ${nonMonetaryService ? `
                    <input type="hidden" name="recommendations[${index}][final_amount]" value="">
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                        This is a non-monetary added service. No amount or frequency check is required.
                    </div>
                ` : ''}

                <div class="mt-4">
                    <label class="label">Recommendation Notes</label>
                    <textarea name="recommendations[${index}][notes]" class="input h-20" data-recommendation-field="notes">${row.notes || ''}</textarea>
                </div>

                ${nonMonetaryService ? '' : `<div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="${statusClass}">
                        ${statusLabel}
                    </div>
                    <button type="button" class="px-4 py-2 rounded-lg border border-[#234E70] text-[#234E70] hover:bg-[#234E70] hover:text-white" data-check-frequency="${index}">
                        Check Frequency
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-600">${row.frequency_message || 'Choose an assistance and run the frequency check.'}</p>`}

                ${!nonMonetaryService && canOverride ? `
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <label class="label">Override Reason</label>
                        <textarea name="recommendations[${index}][frequency_override_reason]" class="input h-24 bg-white" data-recommendation-field="frequency_override_reason" placeholder="Explain why this not eligible assistance should still be recommended.">${row.frequency_override_reason || ''}</textarea>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

function updateRecommendationRow(index, field, value) {
    additionalRecommendations[index][field] = value;

    if (field === 'assistance_type_id') {
        additionalRecommendations[index].assistance_subtype_id = '';
        additionalRecommendations[index].assistance_detail_id = '';
        additionalRecommendations[index].final_amount = '';
        additionalRecommendations[index].mode_of_assistance_id = '';
        additionalRecommendations[index].referral_institution_id = '';
        additionalRecommendations[index].frequency_status = '';
        additionalRecommendations[index].frequency_message = '';
        renderAdditionalRecommendations();
    }

    if (field === 'assistance_subtype_id') {
        additionalRecommendations[index].assistance_detail_id = '';
        additionalRecommendations[index].final_amount = isNonMonetaryService(additionalRecommendations[index])
            ? ''
            : additionalRecommendations[index].final_amount;
        additionalRecommendations[index].mode_of_assistance_id = requiresModeOfAssistance(additionalRecommendations[index])
            ? additionalRecommendations[index].mode_of_assistance_id
            : '';
        additionalRecommendations[index].referral_institution_id = isReferralService(additionalRecommendations[index])
            ? additionalRecommendations[index].referral_institution_id
            : '';
        additionalRecommendations[index].frequency_status = '';
        additionalRecommendations[index].frequency_message = '';
        renderAdditionalRecommendations();
    }
}

async function checkRecommendationFrequency(index) {
    const row = additionalRecommendations[index];

    if (isNonMonetaryService(row)) {
        row.frequency_status = 'eligible';
        row.frequency_message = 'Added non-monetary service. No frequency check is required.';
        renderAdditionalRecommendations();
        return;
    }

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

        row.frequency_status = data.status === 'eligible' ? 'eligible' : 'not_eligible';
        row.frequency_message = data.message || 'Frequency check completed.';
    } catch (error) {
        row.frequency_status = 'not_eligible';
        row.frequency_message = 'Unable to check frequency right now. The server will check again when saving.';
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
.gis-section{
    border:1px solid #e5e7eb;
    border-radius:14px;
    background:#fff;
    padding:18px;
}
.gis-section-title{
    color:#234E70;
    font-weight:800;
    margin-bottom:14px;
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
.status-badge--not-eligible{
    border-color:#fecdd3;
    background:#ffe4e6;
    color:#be123c;
}
</style>

@endsection
