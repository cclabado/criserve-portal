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

    $statusBadgeClasses = [
        'submitted' => 'bg-amber-100 text-amber-800 border border-amber-200',
        'under_review' => 'bg-blue-100 text-blue-800 border border-blue-200',
        'for_approval' => 'bg-violet-100 text-violet-800 border border-violet-200',
        'approved' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'released' => 'bg-green-100 text-green-800 border border-green-200',
        'denied' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'cancelled' => 'bg-slate-200 text-slate-700 border border-slate-300',
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
        @if(in_array($application->status, ['approved', 'released'], true))
        <a href="{{ route('socialworker.certificate', $application->id) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">
            Print Certificate
        </a>
        @endif
    </div>
</div>

<div class="card">
    <h2 class="title">Application Status</h2>

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <span class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-[0.14em] {{ $statusBadgeClasses[$application->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                {{ str_replace('_', ' ', $application->status) }}
            </span>
        </div>

        <div class="text-sm text-slate-500">
            Last updated: {{ $application->updated_at?->format('M d, Y h:i A') ?? '-' }}
        </div>
    </div>

    @if(in_array($application->status, ['denied', 'cancelled'], true) && filled($application->denial_reason))
    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
        <p class="font-semibold">{{ $application->status === 'cancelled' ? 'Cancellation Reason' : 'Denial Reason' }}</p>
        <p class="mt-2">{{ $application->denial_reason }}</p>
    </div>
    @endif
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
            <h2 class="title">Related Accounts & Family Links</h2>
            <p class="text-sm text-gray-500">
                Identity-aware matches and linked client accounts detected from the household records for this case.
            </p>
        </div>

        <button
            type="button"
            onclick="document.getElementById('family-network-modal').classList.remove('hidden')"
            class="inline-flex items-center justify-center rounded-lg bg-[#234E70] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1b405d]">
            Open Family Network
        </button>
    </div>

    @php
        $linkedMembers = collect($familyNetwork['nodes'])->filter(fn ($node) => !empty($node['has_account']))->count();
        $relationshipCount = count($familyNetwork['edges'] ?? []);
    @endphp

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="network-summary-card">
            <p class="network-summary-label">Household Root</p>
            <p class="network-summary-value">{{ $familyNetwork['anchor']['name'] ?? 'Not detected' }}</p>
        </div>

        <div class="network-summary-card">
            <p class="network-summary-label">Relationship Links</p>
            <p class="network-summary-value">{{ $relationshipCount }}</p>
        </div>

        <div class="network-summary-card">
            <p class="network-summary-label">Linked Accounts Found</p>
            <p class="network-summary-value">{{ $linkedMembers }}</p>
        </div>
    </div>
</div>

<div id="family-network-modal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-slate-950/55" onclick="document.getElementById('family-network-modal').classList.add('hidden')"></div>

    <div class="relative mx-auto mt-10 max-h-[85vh] w-[min(900px,88vw)] overflow-y-auto rounded-3xl bg-white p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h3 class="text-xl font-black text-[#163750]">Family Network</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Family-tree view based on recorded relationship labels, arranged from parent to child where possible.
                </p>
            </div>

            <button
                type="button"
                onclick="document.getElementById('family-network-modal').classList.add('hidden')"
                class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                Close
            </button>
        </div>

        @php
            $treePeople = collect();

            foreach (($familyNetwork['edges'] ?? []) as $edge) {
                $memberNode = collect($familyNetwork['nodes'])->firstWhere('id', $edge['to']);

                if (! $memberNode) {
                    continue;
                }

                $roleLabel = strtolower(trim((string) $edge['label']));
                $tier = 'other';

                if (in_array($roleLabel, ['mother', 'father', 'parent', 'guardian', 'grandmother', 'grandfather'], true)) {
                    $tier = 'parent';
                } elseif (in_array($roleLabel, ['son', 'daughter', 'child'], true)) {
                    $tier = 'child';
                } elseif (in_array($roleLabel, ['spouse', 'wife', 'husband', 'partner', 'sister', 'brother', 'sibling'], true)) {
                    $tier = 'same_generation';
                }

                $treePeople->push([
                    'name' => $memberNode['name'],
                    'birthdate' => $memberNode['birthdate'],
                    'role' => $edge['label'],
                    'has_account' => $memberNode['has_account'],
                    'account_email' => $memberNode['account_email'],
                    'tier' => $tier,
                ]);
            }

            $parents = $treePeople->where('tier', 'parent')->sortBy('birthdate')->values();
            $sameGeneration = $treePeople->where('tier', 'same_generation')->sortBy('birthdate')->values();
            $children = $treePeople->where('tier', 'child')->sortBy('birthdate')->values();
            $others = $treePeople->where('tier', 'other')->sortBy('birthdate')->values();
        @endphp

        <div class="family-tree mt-6">
            @if($parents->isNotEmpty())
            <div class="tree-tier tree-tier--parents">
                @foreach($parents as $person)
                <div class="network-card tree-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="network-kicker">{{ $person['role'] }}</p>
                            <p class="network-name text-lg">{{ $person['name'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">Birthdate: {{ $person['birthdate'] ?: 'Not recorded' }}</p>
                        </div>

                        @if($person['has_account'])
                        <span class="network-badge">Account linked</span>
                        @endif
                    </div>

                    @if($person['account_email'])
                    <p class="mt-4 text-xs font-semibold tracking-wide text-sky-700">{{ $person['account_email'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="tree-connector tree-connector--down"></div>
            @endif

            @if($familyNetwork['anchor'])
            <div class="tree-root-wrap">
                <div class="network-anchor">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="network-kicker">{{ $familyNetwork['anchor']['role_display'] ?? $familyNetwork['anchor']['role'] }}</p>
                        @if(!empty($familyNetwork['anchor']['role']))
                        <span class="network-role-badge">
                            {{ $familyNetwork['anchor']['role'] }}
                        </span>
                        @endif
                    </div>
                    <p class="network-name">{{ $familyNetwork['anchor']['name'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">
                        Birthdate: {{ $familyNetwork['anchor']['birthdate'] ?: 'Not recorded' }}
                    </p>

                    @if($familyNetwork['anchor']['has_account'])
                    <p class="mt-4 inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">
                        Linked client account: {{ $familyNetwork['anchor']['account_email'] }}
                    </p>
                    @endif
                </div>
            </div>
            @endif

            @if($sameGeneration->isNotEmpty())
            <div class="tree-connector tree-connector--down"></div>
            <div class="tree-tier tree-tier--siblings">
                @foreach($sameGeneration as $person)
                <div class="network-card tree-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="network-kicker">{{ $person['role'] }}</p>
                            <p class="network-name text-lg">{{ $person['name'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">Birthdate: {{ $person['birthdate'] ?: 'Not recorded' }}</p>
                        </div>

                        @if($person['has_account'])
                        <span class="network-badge">Account linked</span>
                        @endif
                    </div>

                    @if($person['account_email'])
                    <p class="mt-4 text-xs font-semibold tracking-wide text-sky-700">{{ $person['account_email'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if($children->isNotEmpty())
            <div class="tree-connector tree-connector--down"></div>
            <div class="tree-tier tree-tier--children">
                @foreach($children as $person)
                <div class="network-card tree-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="network-kicker">{{ $person['role'] }}</p>
                            <p class="network-name text-lg">{{ $person['name'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">Birthdate: {{ $person['birthdate'] ?: 'Not recorded' }}</p>
                        </div>

                        @if($person['has_account'])
                        <span class="network-badge">Account linked</span>
                        @endif
                    </div>

                    @if($person['account_email'])
                    <p class="mt-4 text-xs font-semibold tracking-wide text-sky-700">{{ $person['account_email'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if($others->isNotEmpty())
            <div class="tree-connector tree-connector--down"></div>
            <div class="tree-tier tree-tier--others">
                @foreach($others as $person)
                <div class="network-card tree-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="network-kicker">{{ $person['role'] }}</p>
                            <p class="network-name text-lg">{{ $person['name'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">Birthdate: {{ $person['birthdate'] ?: 'Not recorded' }}</p>
                        </div>

                        @if($person['has_account'])
                        <span class="network-badge">Account linked</span>
                        @endif
                    </div>

                    @if($person['account_email'])
                    <p class="mt-4 text-xs font-semibold tracking-wide text-sky-700">{{ $person['account_email'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
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
            <span class="muted">Final Amount</span><br>
            &#8369;{{ number_format($application->final_amount, 2) }}
        </div>
    </div>

</div>

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
    padding:16px;
}
.network-summary-card{
    border:1px solid #dbe7f0;
    background:linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    border-radius:18px;
    padding:16px;
}
.network-summary-label{
    font-size:11px;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:#64748b;
    font-weight:800;
}
.network-summary-value{
    margin-top:8px;
    font-size:20px;
    line-height:1.2;
    font-weight:800;
    color:#163750;
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
    font-size:18px;
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
.network-role-badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    background:#dbeafe;
    color:#1d4ed8;
    padding:4px 10px;
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
}
.family-tree{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:12px;
}
.tree-tier{
    width:100%;
    display:grid;
    gap:14px;
}
.tree-tier--parents,
.tree-tier--siblings,
.tree-tier--children,
.tree-tier--others{
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
}
.tree-root-wrap{
    width:100%;
    display:flex;
    justify-content:center;
}
.tree-card{
    min-height:120px;
}
.tree-connector{
    width:2px;
    height:18px;
    background:linear-gradient(180deg, rgba(59,130,246,.35), rgba(148,163,184,.18));
}
</style>

@endsection
