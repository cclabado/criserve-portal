@extends('layouts.app')

@section('content')

@php
    $statusBadgeClasses = match ($application->status) {
        'submitted' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'under_review' => 'bg-blue-100 text-blue-700 border border-blue-200',
        'for_approval' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'released' => 'bg-sky-100 text-sky-700 border border-sky-200',
        'denied' => 'bg-rose-100 text-rose-700 border border-rose-200',
        'cancelled' => 'bg-slate-200 text-slate-700 border border-slate-300',
        default => 'bg-slate-100 text-slate-700 border border-slate-200',
    };
    $recommendationFinalAmount = $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount);
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $recommendationFinalAmount
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
@endphp

<div class="max-w-7xl mx-auto py-8 space-y-8">

<!-- HEADER -->
<div class="mb-6">
    <a href="/client/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
        &larr; BACK TO DASHBOARD
    </a>

    <div>
        <h1 class="text-3xl font-bold text-[#234E70]">
            Application Details
        </h1>

        <p class="text-gray-500">
            Reference No: {{ $application->reference_no }}
        </p>

        <span class="mt-3 inline-flex w-fit items-center rounded-full px-4 py-2 text-xs font-bold uppercase {{ $statusBadgeClasses }}">
            {{ str_replace('_', ' ', $application->status) }}
        </span>
    </div>
</div>

<div class="card">
    <div class="tabs" role="tablist" aria-label="Client application detail sections">
        <button type="button" class="tab-button is-active" data-tab-target="tab-client-info" role="tab" aria-selected="true">
            Client / Beneficiary
        </button>
        <button type="button" class="tab-button" data-tab-target="tab-initial-assessment" role="tab" aria-selected="false">
            Initial Assessment
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

        <div class="section-card">
            <h2 class="title">Beneficiary Information</h2>

            @if($application->beneficiary)
                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="muted">Last Name</span><br>{{ $application->beneficiary->last_name ?? '-' }}</div>
                    <div><span class="muted">First Name</span><br>{{ $application->beneficiary->first_name ?? '-' }}</div>
                    <div><span class="muted">Middle Name</span><br>{{ $application->beneficiary->middle_name ?? '-' }}</div>
                    <div><span class="muted">Extension</span><br>{{ $application->beneficiary->extension_name ?? '-' }}</div>
                </div>

                <div class="mt-4 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                    <div><span class="muted">Sex</span><br>{{ $application->beneficiary->sex ?? '-' }}</div>
                    <div><span class="muted">Birthdate</span><br>{{ $application->beneficiary->birthdate ?? '-' }}</div>
                    <div><span class="muted">Contact Number</span><br>{{ $application->beneficiary->contact_number ?? '-' }}</div>
                </div>

                <div class="mt-4 text-sm">
                    <span class="muted">Relationship to Client</span><br>
                    {{ $application->beneficiary->relationshipData->name ?? '-' }}
                </div>

                <div class="mt-4 text-sm">
                    <span class="muted">Full Address</span><br>
                    {{ $application->beneficiary->full_address ?? '-' }}
                </div>
            @else
                <p class="text-sm text-gray-500">No separate beneficiary information recorded.</p>
            @endif
        </div>

        <div class="section-card">
            <h2 class="title">Family Composition</h2>

            <div class="space-y-3">
                @forelse($application->familyMembers as $fam)
                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-5 py-4 grid gap-3 text-sm md:grid-cols-3">
                        <div>
                            {{ $fam->last_name }}, {{ $fam->first_name }}
                        </div>
                        <div>
                            {{ $fam->relationship }}
                        </div>
                        <div>
                            {{ $fam->birthdate }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No family records.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="tab-initial-assessment" class="tab-panel" role="tabpanel" hidden>
        @if(in_array($application->status, ['denied', 'cancelled'], true) && filled($application->denial_reason))
            <div class="section-card">
                <h2 class="title">{{ $application->status === 'cancelled' ? 'Cancellation Reason' : 'Denial Reason' }}</h2>
                <p class="font-semibold text-gray-800 whitespace-pre-line">{{ $application->denial_reason }}</p>
            </div>
        @endif

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
                <div><span class="muted">Final Amount</span><br>PHP {{ number_format((float) $totalRecommendedAmount, 2) }}</div>
            </div>

            <div class="mt-4 text-sm">
                <span class="muted">Assessment Notes</span><br>
                {{ $application->notes ?: '-' }}
            </div>

            <div class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                <div><span class="muted">Schedule Date</span><br>{{ $application->schedule_date ? \Carbon\Carbon::parse($application->schedule_date)->format('M d, Y h:i A') : '-' }}</div>
                <div>
                    <span class="muted">Meeting Link</span><br>
                    @if($application->meeting_link)
                        <a href="{{ $application->meeting_link }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="font-semibold text-blue-600 hover:underline break-all">
                            {{ $application->meeting_link }}
                        </a>
                    @else
                        -
                    @endif
                </div>
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

    <div id="tab-recommendation" class="tab-panel" role="tabpanel" hidden>
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

<style>
.card{
    background:white;
    padding:24px;
    border-radius:16px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
    border:1px solid #f1f5f9;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = Array.from(document.querySelectorAll('.tab-button'));
    const panels = Array.from(document.querySelectorAll('.tab-panel'));

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
});
</script>

</div>

@endsection
