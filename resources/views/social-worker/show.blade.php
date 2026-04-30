@extends('layouts.app')

@section('content')

@php
    $recommendationFinalAmount = $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount);
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $recommendationFinalAmount
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);

    $statusBadgeClass = match ($application->status) {
        'approved' => 'bg-emerald-100 text-emerald-700',
        'denied' => 'bg-rose-100 text-rose-700',
        'released' => 'bg-sky-100 text-sky-700',
        'for_approval' => 'bg-amber-100 text-amber-700',
        'under_review' => 'bg-violet-100 text-violet-700',
        'submitted' => 'bg-slate-100 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };

    $multiValueSections = [
        'Recent Crisis Types' => $application->recent_crisis_types ?? [],
        'Support Systems' => $application->support_systems ?? [],
        'External Resources' => $application->external_resources ?? [],
        'Self-help Efforts' => $application->self_help_efforts ?? [],
        'Client Sectors' => $application->client_sectors ?? [],
        'Client Sub-categories' => $application->client_sub_categories ?? [],
        'Disability Types' => $application->disability_types ?? [],
    ];

    $incomeSourceLabels = [
        'Salaries/Wages from Employment',
        'Entrepreneurial income/profits',
        'Cash assistance from domestic source',
        'Cash assistance from abroad',
        'Transfers from the government (e.g. 4Ps)',
        'Pension',
        'Other income',
    ];

    $familyNetworkNodes = collect($familyNetwork['nodes'] ?? []);
    $familyNetworkAnchor = $familyNetwork['anchor'] ?? null;
    $familyNetworkEdges = collect($familyNetwork['edges'] ?? []);
    $anchorId = $familyNetworkAnchor['id'] ?? null;
    $familyNetworkTiers = [
        'parents' => collect(),
        'siblings' => collect(),
        'children' => collect(),
        'relatives' => collect(),
    ];

    if ($anchorId) {
        foreach ($familyNetworkEdges as $edge) {
            if (($edge['from'] ?? null) !== $anchorId) {
                continue;
            }

            $node = $familyNetworkNodes->firstWhere('id', $edge['to'] ?? null);
            if (! $node) {
                continue;
            }

            $label = strtolower(trim((string) ($edge['label'] ?? '')));

            if (in_array($label, ['mother', 'father', 'guardian', 'grandmother', 'grandfather', 'grandparent'], true)) {
                $familyNetworkTiers['parents']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            if (in_array($label, ['son', 'daughter', 'child', 'grandchild', 'grandson', 'granddaughter'], true)) {
                $familyNetworkTiers['children']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            if (in_array($label, ['sibling', 'brother', 'sister', 'stepchild', 'stepsibling', 'half-brother', 'half-sister'], true)) {
                $familyNetworkTiers['siblings']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            $familyNetworkTiers['relatives']->push([...$node, 'edge_label' => $edge['label']]);
        }
    }

    $recentCrisisItems = $multiValueSections['Recent Crisis Types'] ?? [];
    $supportSystemItems = $multiValueSections['Support Systems'] ?? [];
    $externalResourcesItems = $multiValueSections['External Resources'] ?? [];
    $selfHelpItems = $multiValueSections['Self-help Efforts'] ?? [];
    $clientSectorItems = $multiValueSections['Client Sectors'] ?? [];
    $clientSubCategoryItems = $multiValueSections['Client Sub-categories'] ?? [];
    $disabilityTypeItems = $multiValueSections['Disability Types'] ?? [];
@endphp

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('socialworker.applications') }}"
               class="text-sm text-gray-500 hover:text-[#234E70]">
                &larr; Back to Applications
            </a>

            <h1 class="mt-2 text-3xl font-bold text-[#234E70]">
                Case Details
            </h1>

            <p class="text-gray-500">
                Reference: {{ $application->reference_no }}
            </p>

            <div class="mt-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] {{ $statusBadgeClass }}">
                    {{ str_replace('_', ' ', $application->status ?? 'unknown') }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('socialworker.general-intake-sheet', $application->id) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center rounded-lg bg-[#234E70] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#18384f]">
                Print GIS
            </a>

            @if(in_array($application->status, ['approved', 'released'], true)
                && strtolower((string) ($application->modeOfAssistance?->name ?? $application->mode_of_assistance)) === 'guarantee letter')
                <a href="{{ route('socialworker.guarantee-letter', $application->id) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Print Guarantee Letter
                </a>
            @endif

            @if(in_array($application->status, ['approved', 'released'], true))
                <a href="{{ route('socialworker.certificate', $application->id) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">
                    Print Certificate of Eligibility
                </a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="tabs" role="tablist" aria-label="Social worker case detail sections">
            <button type="button" class="tab-button is-active" data-tab-target="tab-client-info" role="tab" aria-selected="true">
                Client / Beneficiary
            </button>
            <button type="button" class="tab-button" data-tab-target="tab-initial-assessment" role="tab" aria-selected="false">
                Initial Assessment
            </button>
            <button type="button" class="tab-button" data-tab-target="tab-intake" role="tab" aria-selected="false">
                Social Worker's Intake
            </button>
            <button type="button" class="tab-button" data-tab-target="tab-recommendation" role="tab" aria-selected="false">
                Recommendation
            </button>
        </div>

        <div id="tab-client-info" class="tab-panel is-active" role="tabpanel">
            <div class="section-card">
                <h2 class="title">Client Information</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Last Name</span><br>{{ $application->client->last_name ?? '-' }}</div>
                    <div><span class="muted">First Name</span><br>{{ $application->client->first_name ?? '-' }}</div>
                    <div><span class="muted">Middle Name</span><br>{{ $application->client->middle_name ?? '-' }}</div>
                    <div><span class="muted">Extension</span><br>{{ $application->client->extension_name ?? '-' }}</div>
                </div>

                <div class="mt-4 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Sex</span><br>{{ $application->client->sex ?? '-' }}</div>
                    <div><span class="muted">Birthdate</span><br>{{ $application->client->birthdate ?? '-' }}</div>
                    <div><span class="muted">Civil Status</span><br>{{ $application->client->civil_status ?? '-' }}</div>
                    <div><span class="muted">Contact</span><br>{{ $application->client->contact_number ?? '-' }}</div>
                </div>

                <div class="mt-4 text-sm">
                    <span class="muted">Address</span><br>
                    {{ $application->client->full_address ?? '-' }}
                </div>
            </div>

            @if($application->beneficiary)
                <div class="section-card">
                    <h2 class="title">Beneficiary Information</h2>

                    <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <div><span class="muted">Last Name</span><br>{{ $application->beneficiary->last_name ?? '-' }}</div>
                        <div><span class="muted">First Name</span><br>{{ $application->beneficiary->first_name ?? '-' }}</div>
                        <div><span class="muted">Middle Name</span><br>{{ $application->beneficiary->middle_name ?? '-' }}</div>
                        <div><span class="muted">Extension</span><br>{{ $application->beneficiary->extension_name ?? '-' }}</div>
                    </div>

                    <div class="mt-4 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <div><span class="muted">Sex</span><br>{{ $application->beneficiary->sex ?? '-' }}</div>
                        <div><span class="muted">Birthdate</span><br>{{ $application->beneficiary->birthdate ?? '-' }}</div>
                        <div><span class="muted">Contact</span><br>{{ $application->beneficiary->contact_number ?? '-' }}</div>
                        <div><span class="muted">Relationship to Client</span><br>{{ $application->beneficiary->relationshipData->name ?? '-' }}</div>
                    </div>

                    <div class="mt-4 text-sm">
                        <span class="muted">Address</span><br>
                        {{ $application->beneficiary->full_address ?? '-' }}
                    </div>
                </div>
            @endif

            <div class="section-card">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="title">Family Composition</h2>
                        <p class="text-sm text-gray-500">{{ $application->householdProfileLabel() }}</p>
                    </div>

                    @if(!empty($familyNetwork['nodes']))
                        <button type="button"
                           id="openFamilyNetworkModalBtn"
                           class="inline-flex items-center justify-center rounded-lg bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-200">
                            View Family Network
                        </button>
                    @endif
                </div>

                <div class="mt-4 space-y-2">
                    @forelse($householdMembers as $member)
                        <div class="rounded-xl bg-gray-50 px-5 py-3 grid gap-3 text-sm md:grid-cols-3">
                            <div>{{ $member->last_name }}, {{ $member->first_name }}</div>
                            <div>{{ $member->relationshipData->name ?? $member->relationship ?? '-' }}</div>
                            <div>{{ $member->birthdate ?? '-' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No family members recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div id="tab-initial-assessment" class="tab-panel" role="tabpanel" hidden>
            <div class="section-card">
                <h2 class="title">Assessment Details</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Type</span><br>{{ $application->assistanceType->name ?? '-' }}</div>
                    <div><span class="muted">Subtype</span><br>{{ $application->assistanceSubtype->name ?? '-' }}</div>
                    <div><span class="muted">Assistance Detail</span><br>{{ $application->assistanceDetail->name ?? '-' }}</div>
                    <div><span class="muted">Mode</span><br>{{ $application->modeOfAssistance->name ?? $application->mode_of_assistance ?? '-' }}</div>
                </div>

                <div class="mt-4 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <span class="muted">Service Provider</span><br>{{ $application->serviceProvider->name ?? '-' }}
                        @if($application->serviceProvider?->addressee)
                            <div class="mt-1 text-xs text-slate-500">Addressee: {{ $application->serviceProvider->addressee }}</div>
                        @endif
                        @if($application->serviceProvider?->contact_number)
                            <div class="text-xs text-slate-500">Contact: {{ $application->serviceProvider->contact_number }}</div>
                        @endif
                        @if($application->serviceProvider?->address)
                            <div class="text-xs text-slate-500">{{ $application->serviceProvider->address }}</div>
                        @endif
                    </div>
                    <div><span class="muted">Current Status</span><br>{{ strtoupper(str_replace('_', ' ', $application->status ?? '-')) }}</div>
                    <div><span class="muted">Recommended Amount</span><br>PHP {{ number_format((float) ($application->recommended_amount ?? 0), 2) }}</div>
                    <div><span class="muted">Final Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $totalRecommendedAmount), 2) }}</div>
                    <div><span class="muted">Amount Needed</span><br>PHP {{ number_format((float) ($application->amount_needed ?? 0), 2) }}</div>
                </div>

                <div class="mt-4">
                    <span class="muted">Assessment Notes</span><br>
                    {{ $application->notes ?: '-' }}
                </div>

                <div class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                    <div><span class="muted">Schedule Date</span><br>{{ $application->schedule_date ? $application->schedule_date->format('M d, Y h:i A') : '-' }}</div>
                    <div><span class="muted">Meeting Link</span><br>{{ $application->meeting_link ?: '-' }}</div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">Attachments</h2>

                <div class="space-y-3">
                    @forelse($application->documents as $doc)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-5 py-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">{{ $doc->document_type ?: 'Supporting Document' }}</p>
                                <p class="text-sm font-semibold">{{ $doc->file_name ?? $doc->filename }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->remarks }}</p>
                            </div>

                            <a href="{{ route('documents.show', $doc->id) }}"
                               class="rounded-lg bg-[#234E70] px-4 py-2 text-sm text-white">
                                View Attachment
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No attachments.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div id="tab-intake" class="tab-panel" role="tabpanel" hidden>
            <div class="section-card">
                <h2 class="title">General Intake Sheet</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Client Type</span><br>{{ $application->gis_client_type ?? '-' }}</div>
                    <div><span class="muted">Service Point</span><br>{{ $application->gis_visit_type ?? '-' }}</div>
                    <div><span class="muted">Diagnosis / Cause of Death</span><br>{{ $application->diagnosis_or_cause_of_death ?? '-' }}</div>
                    <div><span class="muted">Amount Needed</span><br>PHP {{ number_format((float) ($application->amount_needed ?? 0), 2) }}</div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">I. Income and Financial Resources</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Working Members</span><br>{{ $application->working_members ?? '-' }}</div>
                    <div><span class="muted">Seasonal Worker Members</span><br>{{ $application->seasonal_worker_members ?? '-' }}</div>
                    <div><span class="muted">Monthly Income</span><br>PHP {{ number_format((float) ($application->monthly_income ?? 0), 2) }}</div>
                    <div><span class="muted">Household Members</span><br>{{ $application->household_members ?? '-' }}</div>
                </div>

                <div class="mt-4 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Occupation / Sources</span><br>{{ $application->occupation_sources ?? '-' }}</div>
                    <div><span class="muted">Insurance Coverage</span><br>{{ $application->insurance_coverage ?? '-' }}</div>
                    <div><span class="muted">Has Insurance Coverage</span><br>{{ $application->has_insurance_coverage ? 'Yes' : 'No' }}</div>
                    <div><span class="muted">Has Savings</span><br>{{ $application->has_savings ? 'Yes' : 'No' }}</div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">II. Budget and Expenses</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Monthly Expenses</span><br>PHP {{ number_format((float) ($application->monthly_expenses ?? 0), 2) }}</div>
                    <div><span class="muted">Savings</span><br>PHP {{ number_format((float) ($application->savings ?? 0), 2) }}</div>
                    <div><span class="muted">Emergency Fund</span><br>{{ $application->emergency_fund ?? '-' }}</div>
                    <div><span class="muted">Total Income Past 6 Months</span><br>PHP {{ number_format((float) ($application->total_income_past_six_months ?? 0), 2) }}</div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">Income in the Past 6 Months</h2>

                <div class="space-y-2">
                    @foreach($incomeSourceLabels as $label)
                        @php($incomeRow = collect($application->income_sources ?? [])->firstWhere('source', $label) ?? [])
                        <div class="rounded-xl bg-gray-50 px-5 py-3 grid gap-3 text-sm md:grid-cols-[1fr,180px]">
                            <div>{{ $label }}</div>
                            <div>PHP {{ number_format((float) ($incomeRow['amount'] ?? 0), 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">III. Severity of the Crisis</h2>

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Disease Duration</span><br>{{ $application->disease_duration ?? '-' }}</div>
                    <div><span class="muted">Experienced Recent Crisis</span><br>{{ is_null($application->experienced_recent_crisis) ? '-' : ($application->experienced_recent_crisis ? 'Yes' : 'No') }}</div>
                    <div><span class="muted">Crisis Type</span><br>{{ $application->crisis_type ?? '-' }}</div>
                    <div><span class="muted">Urgency Level</span><br>{{ $application->urgency_level ?? '-' }}</div>
                </div>

                <div class="mt-5">
                    <p class="muted mb-2">Recent Crisis Types</p>
                    @if(!empty($recentCrisisItems))
                        <div class="flex flex-wrap gap-2">
                            @foreach($recentCrisisItems as $item)
                                <span class="pill">{{ $item }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No data recorded.</p>
                    @endif
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">IV. Availability of Support Systems</h2>

                <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    <div class="flag-item"><span class="muted">Has Family Support</span><br>{{ $application->has_family_support ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Has PhilHealth</span><br>{{ $application->has_philhealth ? 'Yes' : 'No' }}</div>
                </div>

                <div class="mt-5">
                    <p class="muted mb-2">Support Systems</p>
                    @if(!empty($supportSystemItems))
                        <div class="flex flex-wrap gap-2">
                            @foreach($supportSystemItems as $item)
                                <span class="pill">{{ $item }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No data recorded.</p>
                    @endif
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">V. External Resources Tapped by the Family</h2>

                @if(!empty($externalResourcesItems))
                    <div class="flex flex-wrap gap-2">
                        @foreach($externalResourcesItems as $item)
                            <span class="pill">{{ $item }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No data recorded.</p>
                @endif
            </div>

            <div class="section-card">
                <h2 class="title">VI. Self-Help and Client Efforts</h2>

                @if(!empty($selfHelpItems))
                    <div class="flex flex-wrap gap-2">
                        @foreach($selfHelpItems as $item)
                            <span class="pill">{{ $item }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No data recorded.</p>
                @endif
            </div>

            <div class="section-card">
                <h2 class="title">VII. Vulnerability and Risk Factors</h2>

                <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    <div class="flag-item"><span class="muted">Elderly in Household</span><br>{{ $application->has_elderly ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Child in Household</span><br>{{ $application->has_child ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">PWD in Household</span><br>{{ $application->has_pwd ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Pregnant Household Member</span><br>{{ $application->has_pregnant ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Primary Earner Unable to Work</span><br>{{ $application->earner_unable_to_work ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Vulnerable Household Member</span><br>{{ $application->has_vulnerable_household_member ? 'Yes' : 'No' }}</div>
                    <div class="flag-item"><span class="muted">Unstable Employment</span><br>{{ $application->has_unstable_employment ? 'Yes' : 'No' }}</div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="title">VIII. Client Sector</h2>

                <div class="space-y-5">
                    <div>
                        <p class="muted mb-2">Target Sector</p>
                        @if(!empty($clientSectorItems))
                            <div class="flex flex-wrap gap-2">
                                @foreach($clientSectorItems as $item)
                                    <span class="pill">{{ $item }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No data recorded.</p>
                        @endif
                    </div>

                    <div>
                        <p class="muted mb-2">Specify Sub-Category</p>
                        @if(!empty($clientSubCategoryItems))
                            <div class="flex flex-wrap gap-2">
                                @foreach($clientSubCategoryItems as $item)
                                    <span class="pill">{{ $item }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No data recorded.</p>
                        @endif
                    </div>

                    <div>
                        <p class="muted mb-2">Type of Disability</p>
                        @if(!empty($disabilityTypeItems))
                            <div class="flex flex-wrap gap-2">
                                @foreach($disabilityTypeItems as $item)
                                    <span class="pill">{{ $item }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No data recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-recommendation" class="tab-panel" role="tabpanel" hidden>
            <div class="section-card">
                <h2 class="title">Narrative Assessment</h2>

                <div class="space-y-4 text-sm">
                    <div>
                        <span class="muted">Problem Statement</span><br>
                        {{ $application->problem_statement ?: '-' }}
                    </div>

                    <div>
                        <span class="muted">Social Worker Assessment</span><br>
                        {{ $application->social_worker_assessment ?: '-' }}
                    </div>
                </div>
            </div>

            @if($application->assistanceRecommendations->isNotEmpty())
                <div class="section-card">
                    <h2 class="title">Assistance Provided</h2>

                    <div class="mt-4 space-y-3">
                        @foreach($application->assistanceRecommendations as $recommendation)
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="font-semibold text-[#234E70]">
                                            {{ $recommendation->assistanceType->name ?? '-' }}
                                            @if($recommendation->assistanceSubtype)
                                                / {{ $recommendation->assistanceSubtype->name }}
                                            @endif
                                            @if($recommendation->assistanceDetail)
                                                / {{ $recommendation->assistanceDetail->name }}
                                            @endif
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Mode: {{ $recommendation->modeOfAssistance->name ?? '-' }}
                                        </p>

                                        @if($recommendation->referralInstitution)
                                            <p class="mt-1 text-sm text-gray-500">
                                                Referral: {{ $recommendation->referralInstitution->name }}
                                                @if($recommendation->referralInstitution->email)
                                                    ({{ $recommendation->referralInstitution->email }})
                                                @endif
                                            </p>
                                        @endif
                                    </div>

                                    <div class="text-left md:text-right">
                                        <p class="text-xs text-gray-500">Amount</p>
                                        <p class="text-lg font-bold text-[#234E70]">
                                            PHP {{ number_format((float) $recommendation->final_amount, 2) }}
                                        </p>
                                    </div>
                                </div>

                                @if($recommendation->notes)
                                    <p class="mt-3 text-sm text-gray-700">{{ $recommendation->notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</main>

@if(!empty($familyNetwork['nodes']))
    <div id="familyNetworkModal" class="fixed inset-0 z-50 hidden">
        <div id="familyNetworkModalBackdrop" class="absolute inset-0 bg-slate-900/55"></div>

        <div class="relative flex min-h-full items-start justify-center px-3 py-4 sm:px-4 sm:py-6">
            <div class="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-5">
                    <div>
                        <h2 class="text-xl font-black text-sky-950 sm:text-2xl">Family Network</h2>
                        <p class="mt-2 max-w-xl text-sm text-slate-500">
                            Identity-aware family tree based on the saved household composition and linked client accounts for this case.
                        </p>
                    </div>

                    <button type="button"
                        id="closeFamilyNetworkModalBtn"
                        class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-200">
                        Close
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-5 sm:py-5">
                    <div class="space-y-5 sm:space-y-6">
                        @if($familyNetworkTiers['parents']->isNotEmpty())
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach($familyNetworkTiers['parents'] as $node)
                                    <div class="rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm">
                                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                        <p class="mt-3 text-lg font-black leading-tight text-sky-950 sm:text-xl">{{ $node['name'] }}</p>
                                        <p class="mt-2 text-sm text-slate-500">
                                            Birthdate: {{ !empty($node['birthdate']) ? \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') : 'Not recorded' }}
                                        </p>
                                        @if($node['has_account'])
                                            <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($familyNetworkAnchor)
                            <div class="flex justify-center">
                                <div class="w-full max-w-md rounded-[1.75rem] border border-sky-200 bg-gradient-to-br from-white to-sky-50 px-5 py-5 text-center shadow-sm">
                                    <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($familyNetworkAnchor['role_display'] ?? $familyNetworkAnchor['role'] ?? 'HOUSEHOLD ROOT') }}</p>
                                        <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-sky-700">
                                            {{ $familyNetworkAnchor['role'] ?? 'Client Household Root' }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-xl font-black leading-tight text-sky-950 sm:text-2xl">{{ $familyNetworkAnchor['name'] }}</p>
                                    <p class="mt-2 text-sm text-slate-500">
                                        Birthdate: {{ !empty($familyNetworkAnchor['birthdate']) ? \Illuminate\Support\Carbon::parse($familyNetworkAnchor['birthdate'])->format('M d, Y') : 'Not recorded' }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($familyNetworkTiers['siblings']->isNotEmpty())
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach($familyNetworkTiers['siblings'] as $node)
                                    <div class="rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm">
                                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                        <p class="mt-3 text-lg font-black leading-tight text-sky-950 sm:text-xl">{{ $node['name'] }}</p>
                                        <p class="mt-2 text-sm text-slate-500">
                                            Birthdate: {{ !empty($node['birthdate']) ? \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') : 'Not recorded' }}
                                        </p>
                                        @if($node['has_account'])
                                            <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($familyNetworkTiers['children']->isNotEmpty())
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach($familyNetworkTiers['children'] as $node)
                                    <div class="rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm">
                                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                        <p class="mt-3 text-lg font-black leading-tight text-sky-950 sm:text-xl">{{ $node['name'] }}</p>
                                        <p class="mt-2 text-sm text-slate-500">
                                            Birthdate: {{ !empty($node['birthdate']) ? \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') : 'Not recorded' }}
                                        </p>
                                        @if($node['has_account'])
                                            <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($familyNetworkTiers['relatives']->isNotEmpty())
                            <div class="space-y-3 border-t border-slate-200 pt-5">
                                <h4 class="text-sm font-black uppercase tracking-[0.2em] text-slate-500">Other Connected Relatives</h4>
                                <div class="grid gap-4 md:grid-cols-2">
                                    @foreach($familyNetworkTiers['relatives'] as $node)
                                        <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                            <p class="mt-3 text-base font-black leading-tight text-sky-950 sm:text-lg">{{ $node['name'] }}</p>
                                            <p class="mt-2 text-sm text-slate-500">
                                                Birthdate: {{ !empty($node['birthdate']) ? \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') : 'Not recorded' }}
                                            </p>
                                            @if($node['has_account'])
                                                <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
.card{
    background:white;
    padding:24px;
    border-radius:16px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
}
.title{
    font-size:18px;
    font-weight:700;
    color:#234E70;
    margin-bottom:16px;
}
.muted{
    color:#6b7280;
    font-size:12px;
}
.section-card + .section-card{
    margin-top:24px;
}
.tabs{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:24px;
}
.tab-button{
    border:1px solid #dbe7f0;
    background:#f8fafc;
    color:#475569;
    border-radius:999px;
    padding:10px 16px;
    font-size:14px;
    font-weight:700;
    transition:all .2s ease;
}
.tab-button:hover{
    background:#eff6ff;
    color:#1d4ed8;
}
.tab-button.is-active{
    background:#234E70;
    border-color:#234E70;
    color:#fff;
    box-shadow:0 10px 25px rgba(35,78,112,.18);
}
.tab-panel{
    display:none;
}
.tab-panel.is-active{
    display:block;
}
.flag-item{
    border:1px solid #e5e7eb;
    background:#f8fafc;
    border-radius:14px;
    padding:14px 16px;
}
.pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    background:#eff6ff;
    color:#1d4ed8;
    padding:8px 12px;
    font-size:12px;
    font-weight:700;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = Array.from(document.querySelectorAll('.tab-button'));
    const panels = Array.from(document.querySelectorAll('.tab-panel'));
    const familyNetworkModal = document.getElementById('familyNetworkModal');
    const openFamilyNetworkModalBtn = document.getElementById('openFamilyNetworkModalBtn');
    const closeFamilyNetworkModalBtn = document.getElementById('closeFamilyNetworkModalBtn');
    const familyNetworkModalBackdrop = document.getElementById('familyNetworkModalBackdrop');

    const activateTab = (targetId) => {
        buttons.forEach((button) => {
            const isActive = button.dataset.tabTarget === targetId;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            const isActive = panel.id === targetId;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.tabTarget));
    });

    if (familyNetworkModal && openFamilyNetworkModalBtn) {
        const openModal = () => {
            familyNetworkModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            familyNetworkModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        openFamilyNetworkModalBtn.addEventListener('click', openModal);
        closeFamilyNetworkModalBtn?.addEventListener('click', closeModal);
        familyNetworkModalBackdrop?.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !familyNetworkModal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }
});
</script>

@endsection
