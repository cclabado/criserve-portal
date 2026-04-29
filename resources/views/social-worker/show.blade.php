@extends('layouts.app')

@section('content')

@php
    $frequencyBadgeClasses = [
        'eligible' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'review_required' => 'bg-amber-100 text-amber-800 border border-amber-200',
        'blocked' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'overridden' => 'bg-sky-100 text-sky-800 border border-sky-200',
        'not_applicable' => 'bg-slate-100 text-slate-700 border border-slate-200',
    ];
@endphp

<main class="p-8 max-w-6xl mx-auto space-y-6">

<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div>
        <a href="{{ route('socialworker.applications') }}"
           class="text-sm text-gray-500 hover:text-[#234E70]">
            &larr; Back to Applications
        </a>

        <h1 class="text-3xl font-bold text-[#234E70] mt-2">
            Case Details
        </h1>

        <p class="text-gray-500">
            Reference: {{ $application->reference_no }}
        </p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('socialworker.general-intake-sheet', $application->id) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center justify-center rounded-lg bg-[#234E70] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#18384f]">
            Print GIS
        </a>
    </div>
</div>

<!-- CLIENT -->
<div class="card">
    <h2 class="title">Client Information</h2>

    <div class="grid grid-cols-4 gap-4 text-sm">
        <div><span class="muted">Last Name</span><br>{{ $application->client->last_name }}</div>
        <div><span class="muted">First Name</span><br>{{ $application->client->first_name }}</div>
        <div><span class="muted">Middle Name</span><br>{{ $application->client->middle_name }}</div>
        <div><span class="muted">Extension</span><br>{{ $application->client->extension_name }}</div>
    </div>

    <div class="grid grid-cols-4 gap-4 text-sm mt-4">
        <div><span class="muted">Sex</span><br>{{ $application->client->sex }}</div>
        <div><span class="muted">Birthdate</span><br>{{ $application->client->birthdate }}</div>
        <div><span class="muted">Civil Status</span><br>{{ $application->client->civil_status }}</div>
        <div><span class="muted">Contact</span><br>{{ $application->client->contact_number }}</div>
    </div>

    <div class="mt-4 text-sm">
        <span class="muted">Address</span><br>
        {{ $application->client->full_address }}
    </div>
</div>

<!-- BENEFICIARY -->
@if($application->beneficiary)
<div class="card">
    <h2 class="title">Beneficiary Information</h2>

    <div class="grid grid-cols-4 gap-4 text-sm">
        <div>{{ $application->beneficiary->last_name }}</div>
        <div>{{ $application->beneficiary->first_name }}</div>
        <div>{{ $application->beneficiary->middle_name }}</div>
        <div>{{ $application->beneficiary->extension_name }}</div>
    </div>

    <div class="grid grid-cols-4 gap-4 text-sm mt-4">
        <div>{{ $application->beneficiary->sex }}</div>
        <div>{{ $application->beneficiary->birthdate }}</div>
        <div>{{ $application->beneficiary->contact_number }}</div>
        <div>-</div>
    </div>

    <div class="mt-4 text-sm">
        {{ $application->beneficiary->full_address }}
    </div>
</div>
@endif

<!-- FAMILY -->
<div class="card">
    <h2 class="title">Family Composition</h2>

    <p class="text-sm text-gray-500 mb-3">
        {{ $application->householdProfileLabel() }}
    </p>

    @if($application->beneficiary?->relationshipData)
    <p class="text-xs text-gray-400 mb-4">
        Client's relationship to beneficiary: {{ $application->beneficiary->relationshipData->name }}
    </p>
    @endif

    <div class="space-y-2 mt-4">

        @forelse($householdMembers as $fam)
        <div class="bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-3 gap-4 text-sm">
            <div>
                {{ $fam->last_name }}, {{ $fam->first_name }}
            </div>

            <div>
                {{ $fam->relationshipData->name ?? $fam->relationship }}
            </div>

            <div>
                {{ $fam->birthdate }}
            </div>
        </div>
        @empty
        <p class="text-gray-500">No family members.</p>
        @endforelse

    </div>
</div>

@if(!empty($familyNetwork['nodes']))
<div class="card">
    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="title">Family Network</h2>
            <p class="text-sm text-gray-500">
                Connected people detected from the client, beneficiary, and household records for this case.
            </p>
        </div>
    </div>

    @if($familyNetwork['anchor'])
    <div class="network-anchor">
        <p class="network-kicker">{{ $familyNetwork['anchor']['role'] }}</p>
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="network-name">{{ $familyNetwork['anchor']['name'] }}</p>
                <p class="text-sm text-slate-500">
                    Birthdate: {{ $familyNetwork['anchor']['birthdate'] ?: 'Not recorded' }}
                </p>
            </div>

            @if($familyNetwork['anchor']['has_account'])
            <span class="network-badge">
                Linked client account: {{ $familyNetwork['anchor']['account_email'] }}
            </span>
            @endif
        </div>
    </div>
    @endif

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        @foreach($familyNetwork['edges'] as $edge)
            @php
                $memberNode = collect($familyNetwork['nodes'])->firstWhere('id', $edge['to']);
            @endphp

            @if($memberNode)
            <div class="network-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="network-kicker">{{ $edge['label'] }}</p>
                        <p class="network-name text-lg">{{ $memberNode['name'] }}</p>
                        <p class="text-sm text-slate-500">
                            Birthdate: {{ $memberNode['birthdate'] ?: 'Not recorded' }}
                        </p>
                    </div>

                    @if($memberNode['has_account'])
                    <span class="network-badge">
                        Account linked
                    </span>
                    @endif
                </div>

                @if($memberNode['account_email'])
                <p class="mt-3 text-xs font-medium text-sky-700">
                    {{ $memberNode['account_email'] }}
                </p>
                @endif
            </div>
            @endif
        @endforeach
    </div>
</div>
@endif

<!-- ASSISTANCE -->
<div class="card">
    <h2 class="title">Assessment Details</h2>

    <div class="grid grid-cols-4 gap-4 text-sm">

        <div>
            <span class="muted">Type</span><br>
            {{ $application->assistanceType->name ?? '-' }}
        </div>

        <div>
            <span class="muted">Subtype</span><br>
            {{ $application->assistanceSubtype->name ?? '-' }}
        </div>

        <div>
            <span class="muted">Assistance Detail</span><br>
            {{ $application->assistanceDetail->name ?? '-' }}
        </div>

        <div>
            <span class="muted">Mode</span><br>
            {{ $application->modeOfAssistance->name ?? $application->mode_of_assistance }}
        </div>

    </div>

    <div class="mt-4">
        <span class="muted">Notes</span><br>
        {{ $application->notes }}
    </div>

    <div class="mt-4">
        <span class="muted">Schedule</span><br>
        {{ $application->schedule_date }}
    </div>

    <div class="mt-4">
        <span class="muted">Meeting Link</span><br>
        {{ $application->meeting_link }}
    </div>
</div>

<!-- INTAKE -->
<div class="card">
    <h2 class="title">Intake Details</h2>

    <div class="grid grid-cols-3 gap-4 text-sm">
        <div><span class="muted">Monthly Income</span><br>&#8369;{{ number_format($application->monthly_income, 2) }}</div>
        <div><span class="muted">Monthly Expenses</span><br>&#8369;{{ number_format($application->monthly_expenses, 2) }}</div>
        <div><span class="muted">Savings</span><br>&#8369;{{ number_format($application->savings, 2) }}</div>

        <div><span class="muted">Crisis</span><br>{{ $application->crisis_type }}</div>
        <div><span class="muted">Urgency</span><br>{{ $application->urgency_level }}</div>
        <div><span class="muted">Members</span><br>{{ $application->household_members }}</div>
    </div>

    <div class="mt-4">
        <span class="muted">Problem Statement</span><br>
        {{ $application->problem_statement }}
    </div>

    <div class="mt-4">
        <span class="muted">Assessment</span><br>
        {{ $application->social_worker_assessment }}
    </div>

    <div class="grid grid-cols-2 gap-4 mt-4">
        <div>
            <span class="muted">Recommended Amount</span><br>
            &#8369;{{ number_format($application->recommended_amount, 2) }}
        </div>

        <div>
            <span class="muted">Total Recommended Amount</span><br>
            &#8369;{{ number_format($application->final_amount, 2) }}
        </div>
    </div>

</div>

@if($application->assistanceRecommendations->isNotEmpty())
<div class="card">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="title mb-1">Specific Assistance Recommended</h2>
            <p class="text-sm text-gray-500">Certificate of Eligibility is available after approval.</p>
        </div>

        @if(in_array($application->status, ['approved', 'released'], true))
        <a href="{{ route('socialworker.certificate', $application->id) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">
            Print Certificate of Eligibility
        </a>
        @endif
    </div>

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
                            @if($recommendation->referralInstitution)
                                / {{ $recommendation->referralInstitution->name }}
                            @endif
                        </p>

                        @if($recommendation->notes)
                            <p class="mt-2 text-sm text-gray-700">{{ $recommendation->notes }}</p>
                        @endif
                    </div>

                    <div class="text-left md:text-right">
                        <p class="text-xs text-gray-500">Recommended Amount</p>
                        <p class="text-lg font-bold text-[#234E70]">
                            PHP {{ number_format($recommendation->final_amount, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@if($application->frequency_status)
<div class="card">
    <h2 class="title">Frequency of Assistance</h2>

    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="muted">Status</span><br>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase {{ $frequencyBadgeClasses[$application->frequency_status] ?? $frequencyBadgeClasses['not_applicable'] }}">
                {{ str_replace('_', ' ', $application->frequency_status) }}
            </span>
        </div>

        <div>
            <span class="muted">Rule Basis</span><br>
            {{ $application->frequencyBasisApplication?->reference_no ?? 'No prior basis application' }}
        </div>
    </div>

    <div class="mt-4">
        <span class="muted">Review Notes</span><br>
        {{ $application->frequency_message ?: ($application->frequencyRule?->notes ?? '-') }}
    </div>

    <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
        <div>
            <span class="muted">Reference Date</span><br>
            {{ $application->frequency_reference_date ? \Carbon\Carbon::parse($application->frequency_reference_date)->format('M d, Y') : '-' }}
        </div>

        <div>
            <span class="muted">Incident / Admission Reference</span><br>
            {{ $application->frequency_case_key ?: '-' }}
        </div>
    </div>

    @if($application->frequency_override_reason)
    <div class="mt-4">
        <span class="muted">Justification</span><br>
        {{ $application->frequency_override_reason }}
    </div>
    @endif
</div>
@endif

<!-- ATTACHMENTS -->
<div class="card">
    <h2 class="title">Attachments</h2>

    <div class="space-y-3 mt-4">

        @forelse($application->documents as $doc)

        <div class="bg-gray-50 rounded-xl px-5 py-3 flex justify-between items-center">

            <div>
                <p class="font-semibold text-sm">
                    {{ $doc->file_name ?? $doc->filename }}
                </p>

                <p class="text-xs text-gray-500">
                    {{ $doc->remarks }}
                </p>
            </div>

            <a href="{{ route('documents.show', $doc->id) }}"
               class="px-4 py-2 bg-[#234E70] text-white rounded-lg text-sm">
                View Attachment
            </a>

        </div>

        @empty
        <p class="text-gray-500">No attachments uploaded.</p>
        @endforelse

    </div>
</div>

</main>

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
.network-anchor,
.network-card{
    border:1px solid #dbe7f0;
    background:linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    border-radius:18px;
    padding:18px;
}
.network-anchor{
    margin-top:8px;
}
.network-kicker{
    font-size:11px;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:#64748b;
    font-weight:800;
}
.network-name{
    margin-top:6px;
    font-size:22px;
    line-height:1.2;
    font-weight:800;
    color:#163750;
}
.network-badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    background:#e0f2fe;
    color:#075985;
    padding:6px 10px;
    font-size:12px;
    font-weight:700;
}
</style>

@endsection
