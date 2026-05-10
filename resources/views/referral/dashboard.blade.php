@extends('layouts.app')

@section('content')

@php
    $title = $isOfficer ? 'Referral Officer Workspace' : (($institution?->name ?: 'Referral Institution').' Referral Desk');
    $kicker = $isOfficer ? 'Referral Officer' : 'Referral Institution';
    $submissionStatusOptions = $institutionReferralStatusOptions ?? [];
    $showBeneficiaryFields = old('subject_type', 'client') === 'beneficiary';
@endphp

<main class="space-y-6">
    <section class="referral-hero">
        <div>
            <p class="referral-kicker">{{ $kicker }}</p>
            <h1 class="referral-title">{{ $title }}</h1>
            <p class="referral-copy">
                {{ $isOfficer
                    ? 'Monitor institution-submitted referrals together with outgoing case referrals, then act on the ones that need review.'
                    : 'Receive assigned referrals, submit new client or beneficiary referrals, and keep the referral officer updated from one workspace.' }}
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="referral-alert referral-alert--success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="referral-alert referral-alert--error">
            <p class="font-semibold">Please review the referral details.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="space-y-4">
        <div class="section-head">
            <div>
                <p class="panel-kicker">Assigned Queue</p>
                <h2 class="panel-title">Referral Recommendations</h2>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="metric-card"><p class="metric-label">Total Referrals</p><p class="metric-value">{{ number_format($stats['total']) }}</p></article>
            <article class="metric-card"><p class="metric-label">Pending</p><p class="metric-value">{{ number_format($stats['pending']) }}</p></article>
            <article class="metric-card"><p class="metric-label">In Progress</p><p class="metric-value">{{ number_format($stats['in_progress']) }}</p></article>
            <article class="metric-card"><p class="metric-label">Completed</p><p class="metric-value">{{ number_format($stats['completed']) }}</p></article>
        </div>
    </section>

    <section class="panel-card">
        <div class="section-head">
            <div>
                <p class="panel-kicker">Filters</p>
                <h2 class="panel-title">Outgoing Referral Queue</h2>
            </div>
        </div>

        <form method="GET" action="{{ $indexRoute }}" class="mt-6 grid gap-4 md:grid-cols-3">
            <div>
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Reference no, client, institution">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            @if($isOfficer)
                <div>
                    <label class="label">Institution</label>
                    <select name="institution_id" class="input">
                        <option value="">All institutions</option>
                        @foreach($institutions as $item)
                            <option value="{{ $item->id }}" @selected($filters['institution_id'] === (string) $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="md:col-span-3 flex justify-end gap-3">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ $indexRoute }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel-card">
        <div class="section-head">
            <div>
                <p class="panel-kicker">Assigned Referrals</p>
                <h2 class="panel-title">Case Referral Queue</h2>
            </div>
        </div>

        <div class="table-wrap mt-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        @if($isOfficer)<th>Institution</th>@endif
                        <th>Referral</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $referral)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-800">{{ $referral->application?->reference_no ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $referral->referred_at?->format('M d, Y h:i A') ?: '-' }}</div>
                            </td>
                            <td>
                                <div>{{ trim(($referral->application?->client?->first_name ?? '').' '.($referral->application?->client?->last_name ?? '')) ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $referral->application?->socialWorker?->name ?: 'No assigned social worker' }}</div>
                            </td>
                            @if($isOfficer)
                                <td>{{ $referral->referralInstitution?->name ?: '-' }}</td>
                            @endif
                            <td>
                                <div>{{ $referral->assistanceType?->name ?: 'Referral' }}</div>
                                <div class="text-xs text-slate-500">{{ $referral->notes ?: 'No referral notes' }}</div>
                            </td>
                            <td>
                                <span class="user-role-pill">{{ ucwords(str_replace('_', ' ', $referral->referral_status ?: 'pending')) }}</span>
                                <div class="text-xs text-slate-500 mt-2">{{ $referral->referral_notes ?: 'No institution feedback yet.' }}</div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route($updateRouteBase, $referral) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="referral_status" class="input text-sm">
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status }}" @selected(($referral->referral_status ?: 'pending') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="referral_notes" class="input text-sm" rows="2" placeholder="Add referral feedback">{{ $referral->referral_notes }}</textarea>
                                    <button type="submit" class="btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isOfficer ? 6 : 5 }}" class="text-center text-slate-500">No assigned referrals found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $referrals->links() }}
        </div>
    </section>

    @if(! $isOfficer)
        <section class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <article class="panel-card">
                <div class="section-head">
                    <div>
                        <p class="panel-kicker">New Application</p>
                        <h2 class="panel-title">Submit Referred Assistance Application</h2>
                        <p class="panel-copy">Use the full 4-step application form for the client or beneficiary, including family composition and document uploads.</p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-600">
                        The referred application now follows the same intake depth as the client portal submission:
                        client details, beneficiary details, family composition, assistance selection, and document checklist uploads.
                    </p>
                    <div class="mt-5 flex justify-end">
                        <a href="{{ route('referral-institution.applications.create') }}" class="btn-primary">
                            Open Full Application Form
                        </a>
                    </div>
                </div>
            </article>

            <article class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                    <article class="metric-card"><p class="metric-label">Submitted</p><p class="metric-value">{{ number_format($submissionStats['total'] ?? 0) }}</p></article>
                    <article class="metric-card"><p class="metric-label">Pending Review</p><p class="metric-value">{{ number_format($submissionStats['pending'] ?? 0) }}</p></article>
                    <article class="metric-card"><p class="metric-label">In Progress</p><p class="metric-value">{{ number_format($submissionStats['in_progress'] ?? 0) }}</p></article>
                    <article class="metric-card"><p class="metric-label">Completed</p><p class="metric-value">{{ number_format($submissionStats['completed'] ?? 0) }}</p></article>
                </div>

                <section class="panel-card">
                    <div class="section-head">
                        <div>
                            <p class="panel-kicker">Tracking</p>
                            <h2 class="panel-title">Submitted Referrals</h2>
                        </div>
                    </div>

                    <form method="GET" action="{{ $indexRoute }}" class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">Search</label>
                            <input type="text" name="submission_search" class="input" value="{{ $filters['submission_search'] }}" placeholder="Client, beneficiary, assistance">
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select name="submission_status" class="input">
                                <option value="all" @selected($filters['submission_status'] === 'all')>All statuses</option>
                                @foreach($submissionStatusOptions as $status)
                                    <option value="{{ $status }}" @selected($filters['submission_status'] === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-3">
                            <button type="submit" class="btn-primary">Filter Submissions</button>
                            <a href="{{ $indexRoute }}" class="btn-secondary">Reset</a>
                        </div>
                    </form>

                    <div class="table-wrap mt-6">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Person</th>
                                    <th>Requested Assistance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($submittedReferrals ?? collect()) as $submission)
                                    <tr>
                                        <td>
                                            <div class="font-semibold text-slate-800">
                                                {{ trim($submission->client_first_name.' '.$submission->client_last_name) }}
                                                @if($submission->subject_type === 'beneficiary' && $submission->beneficiary_first_name)
                                                    <span class="table-inline-note">for {{ trim($submission->beneficiary_first_name.' '.$submission->beneficiary_last_name) }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $submission->application?->reference_no ?: 'No application reference yet' }}
                                                • {{ $submission->submitted_at?->format('M d, Y h:i A') ?: '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div>{{ $submission->requested_assistance ?: 'General referral' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $submission->application?->assistanceSubtype?->name ?: ($submission->case_summary ?: 'No case summary provided.') }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="user-role-pill">{{ ucwords(str_replace('_', ' ', $submission->status ?: 'pending')) }}</span>
                                            <div class="text-xs text-slate-500 mt-2">{{ $submission->officer_notes ?: 'Waiting for referral officer review.' }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-slate-500">No submitted institution referrals found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ ($submittedReferrals ?? collect())->links() ?? '' }}
                    </div>
                </section>
            </article>
        </section>
    @else
        <section class="space-y-4">
            <div class="section-head">
                <div>
                    <p class="panel-kicker">Institution Referrals</p>
                    <h2 class="panel-title">Submitted by Referral Institutions</h2>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="metric-card"><p class="metric-label">Submitted</p><p class="metric-value">{{ number_format($institutionReferralStats['total'] ?? 0) }}</p></article>
                <article class="metric-card"><p class="metric-label">Pending Review</p><p class="metric-value">{{ number_format($institutionReferralStats['pending'] ?? 0) }}</p></article>
                <article class="metric-card"><p class="metric-label">In Progress</p><p class="metric-value">{{ number_format($institutionReferralStats['in_progress'] ?? 0) }}</p></article>
                <article class="metric-card"><p class="metric-label">Completed</p><p class="metric-value">{{ number_format($institutionReferralStats['completed'] ?? 0) }}</p></article>
            </div>
        </section>

        <section class="panel-card">
            <div class="section-head">
                <div>
                    <p class="panel-kicker">Filters</p>
                    <h2 class="panel-title">Institution Submission Queue</h2>
                </div>
            </div>

            <form method="GET" action="{{ $indexRoute }}" class="mt-6 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="label">Search</label>
                    <input type="text" name="submission_search" class="input" value="{{ $filters['submission_search'] }}" placeholder="Client, beneficiary, assistance">
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="submission_status" class="input">
                        <option value="all" @selected($filters['submission_status'] === 'all')>All statuses</option>
                        @foreach($submissionStatusOptions as $status)
                            <option value="{{ $status }}" @selected($filters['submission_status'] === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Institution</label>
                    <select name="institution_id" class="input">
                        <option value="">All institutions</option>
                        @foreach($institutions as $item)
                            <option value="{{ $item->id }}" @selected($filters['institution_id'] === (string) $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end gap-3">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="{{ $indexRoute }}" class="btn-secondary">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Client / Beneficiary</th>
                            <th>Requested Assistance</th>
                            <th>Status</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($institutionReferrals ?? collect()) as $submission)
                            <tr>
                                <td>
                                    <div class="font-semibold text-slate-800">{{ $submission->institution?->name ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $submission->application?->reference_no ?: 'No application reference yet' }}
                                        • {{ $submission->submitted_at?->format('M d, Y h:i A') ?: '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ trim($submission->client_first_name.' '.$submission->client_last_name) }}</div>
                                    <div class="text-xs text-slate-500">{{ $submission->client_birthdate?->format('M d, Y') ?: 'No client birthdate' }}</div>
                                    @if($submission->subject_type === 'beneficiary' && $submission->beneficiary_first_name)
                                        <div class="mt-2 text-xs text-slate-600">
                                            Beneficiary: {{ trim($submission->beneficiary_first_name.' '.$submission->beneficiary_last_name) }}
                                            @if($submission->beneficiary_birthdate)
                                                , {{ $submission->beneficiary_birthdate->format('M d, Y') }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $submission->requested_assistance ?: 'General referral' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $submission->application?->assistanceSubtype?->name ?: ($submission->case_summary ?: 'No case summary provided.') }}
                                    </div>
                                    @if($submission->institution_notes)
                                        <div class="text-xs text-slate-500 mt-2">Institution note: {{ $submission->institution_notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="user-role-pill">{{ ucwords(str_replace('_', ' ', $submission->status ?: 'pending')) }}</span>
                                    <div class="text-xs text-slate-500 mt-2">{{ $submission->officer_notes ?: 'No officer feedback yet.' }}</div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('referral-officer.institution-referrals.update', $submission) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="input text-sm">
                                            @foreach($submissionStatusOptions as $status)
                                                <option value="{{ $status }}" @selected(($submission->status ?: 'pending') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="officer_notes" class="input text-sm" rows="2" placeholder="Add officer notes">{{ $submission->officer_notes }}</textarea>
                                        <button type="submit" class="btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500">No institution-submitted referrals found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ ($institutionReferrals ?? collect())->links() ?? '' }}
            </div>
        </section>
    @endif
</main>

<style>
.referral-hero,.panel-card,.metric-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.referral-hero{
    padding:28px 30px;
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-end;
    background:
        radial-gradient(circle at top left, rgba(184, 220, 244, .55), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #edf5fb 100%);
}
.referral-kicker,.panel-kicker,.metric-label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.referral-title,.panel-title{
    color:#163750;
    font-weight:900;
}
.referral-title{
    font-size:34px;
    margin-top:10px;
}
.referral-copy,.panel-copy{
    margin-top:10px;
    color:#64748b;
}
.panel-card,.metric-card{ padding:22px; }
.metric-value{
    margin-top:10px;
    font-size:30px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.section-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
}
.input{
    appearance:none;
    border:1px solid #d4dde6;
    border-radius:14px;
    padding:12px 14px;
    width:100%;
    background:#fff;
}
.referral-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.referral-alert--success{ background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
.referral-alert--error{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.table-wrap{ overflow:auto; }
.data-table{
    width:100%;
    border-collapse:collapse;
}
.data-table th,.data-table td{
    padding:14px 10px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
    font-size:14px;
}
.data-table th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
}
.btn-primary,.btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 16px;
    border-radius:14px;
    font-weight:700;
}
.btn-primary{
    background:#234E70;
    color:#fff;
}
.btn-secondary{
    background:#e6eef5;
    color:#234E70;
}
.label{
    display:block;
    margin-bottom:8px;
    font-size:12px;
    font-weight:800;
    color:#475569;
    letter-spacing:.04em;
    text-transform:uppercase;
}
.user-role-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    background:#eaf3fb;
    color:#234E70;
    font-size:12px;
    font-weight:800;
}
.form-group-title{
    margin-bottom:14px;
    font-size:13px;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#234E70;
}
.table-inline-note{
    color:#64748b;
    font-size:12px;
    font-weight:600;
}
.hidden{
    display:none;
}
@media (max-width: 960px){
    .referral-hero,.section-head{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const subjectSelect = document.querySelector('[data-referral-subject]');
    const beneficiarySection = document.querySelector('[data-beneficiary-fields]');

    if (!subjectSelect || !beneficiarySection) {
        return;
    }

    const toggleBeneficiaryFields = () => {
        beneficiarySection.classList.toggle('hidden', subjectSelect.value !== 'beneficiary');
    };

    subjectSelect.addEventListener('change', toggleBeneficiaryFields);
    toggleBeneficiaryFields();
});
</script>

@endsection
