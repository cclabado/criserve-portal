@extends('layouts.app')

@section('content')

@php($isReportingOfficer = auth()->user()?->role === 'reporting_officer')

<main class="space-y-6">

    <section class="admin-hero">
        <div>
            <p class="admin-kicker">{{ $isReportingOfficer ? 'Reporting Officer' : 'Administrator' }}</p>
            <h1 class="admin-title">{{ $isReportingOfficer ? 'Reporting Dashboard' : 'System Access and Library Management' }}</h1>
            <p class="admin-subtitle">
                {{ $isReportingOfficer
                    ? 'Review operational metrics and jump straight into report generation without exposing the rest of the administrative workspace.'
                    : 'The administrator role can oversee every module, monitor users and applications, and maintain the library records used across the workflow.' }}
            </p>
        </div>

        <div class="admin-actions">
            @if($isReportingOfficer)
                <a href="{{ route('reporting.reports') }}" class="admin-action-card">
                    <span class="material-symbols-outlined">assessment</span>
                    <div>
                        <p class="admin-action-title">Open Reports</p>
                        <p class="admin-action-copy">Build filtered exports and review reporting summaries.</p>
                    </div>
                </a>
            @else
                <a href="/social-worker/applications" class="admin-action-card">
                    <span class="material-symbols-outlined">folder_open</span>
                    <div>
                        <p class="admin-action-title">Open Applications</p>
                        <p class="admin-action-copy">View the full social worker queue.</p>
                    </div>
                </a>

                <a href="/approving-officer/applications" class="admin-action-card">
                    <span class="material-symbols-outlined">fact_check</span>
                    <div>
                        <p class="admin-action-title">Open Approvals</p>
                        <p class="admin-action-copy">Jump into the approving officer queue.</p>
                    </div>
                </a>
            @endif
        </div>
    </section>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-alert--error">
            <p class="font-semibold">Please review the form input.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="metric-card">
            <p class="metric-label">Users</p>
            <p class="metric-value">{{ number_format($stats['total_users']) }}</p>
            <p class="metric-copy">Registered accounts across all roles.</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">Applications</p>
            <p class="metric-value">{{ number_format($stats['total_applications']) }}</p>
            <p class="metric-copy">All submitted cases in the system.</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">For Approval</p>
            <p class="metric-value">{{ number_format($stats['for_approval']) }}</p>
            <p class="metric-copy">Cases waiting for final decision.</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">Released</p>
            <p class="metric-value">{{ number_format($stats['released']) }}</p>
            <p class="metric-copy">Approved cases already released.</p>
        </article>

        @unless($isReportingOfficer)
            <article class="metric-card">
                <p class="metric-label">Open Support Tickets</p>
                <p class="metric-value">{{ number_format($stats['open_support_tickets']) }}</p>
                <p class="metric-copy">Support requests waiting for review.</p>
            </article>
        @endunless
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr,.95fr]">
        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">{{ $isReportingOfficer ? 'Reporting Workspace' : 'User Management' }}</p>
                    <h2 class="panel-title">{{ $isReportingOfficer ? 'Reports and Exports' : 'Separate User Workspace' }}</h2>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @if($isReportingOfficer)
                    <div class="soft-card">
                        <p class="soft-card-title">Generate Operational Reports</p>
                        <p class="soft-card-copy">
                            Use the reports workspace to filter applications by date, status, assistance type, and responsible staff.
                        </p>
                    </div>

                    <div class="soft-card">
                        <p class="soft-card-title">Export CSV Snapshots</p>
                        <p class="soft-card-copy">
                            Download the filtered report set as a CSV file for submission, sharing, or further analysis.
                        </p>
                    </div>
                @else
                    <div class="soft-card">
                        <p class="soft-card-title">Edit User Details</p>
                        <p class="soft-card-copy">
                            Use the dedicated user-management page to update names, email addresses,
                            profile information, and account roles from one place.
                        </p>
                    </div>

                    <div class="soft-card">
                        <p class="soft-card-title">Role Controls</p>
                        <p class="soft-card-copy">
                            Role changes are handled there as well, with guardrails to prevent removing the last administrator.
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ $isReportingOfficer ? route('reporting.reports') : route('admin.reports') }}" class="btn-primary inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">assessment</span>
                    Open Reports
                </a>

                @unless($isReportingOfficer)
                    <a href="{{ route('admin.users') }}" class="btn-secondary inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                        Open User Management
                    </a>

                    <a href="{{ route('admin.libraries') }}" class="btn-secondary inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">library_books</span>
                        Open Libraries
                    </a>

                    <a href="{{ route('admin.support-tickets') }}" class="btn-secondary inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">support_agent</span>
                        Open Support Tickets
                    </a>
                @endunless
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Operations</p>
                    <h2 class="panel-title">Recent Applications</h2>
                </div>
            </div>

            <div class="space-y-3 mt-4">
                @forelse($applications as $application)
                    <article class="application-item">
                        <div>
                            <p class="application-ref">{{ $application->reference_no }}</p>
                            <p class="application-name">{{ $application->client->first_name ?? '' }} {{ $application->client->last_name ?? '' }}</p>
                            <p class="application-meta">{{ $application->assistanceType->name ?? 'No type' }}</p>
                        </div>

                        <span class="role-pill">{{ str_replace('_', ' ', $application->status) }}</span>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No applications found.</p>
                @endforelse
            </div>
        </div>
    </section>

</main>

<style>
.admin-hero{
    background:
        radial-gradient(circle at top right, rgba(120, 177, 214, .35), transparent 35%),
        linear-gradient(135deg, #163750 0%, #234E70 55%, #2f6a91 100%);
    color:#fff;
    border-radius:24px;
    padding:32px;
    display:grid;
    gap:24px;
}
.admin-kicker,
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#6b7a89;
}
.admin-kicker{
    color:rgba(255,255,255,.72);
}
.admin-title{
    font-size:32px;
    font-weight:900;
    line-height:1.05;
    margin-top:8px;
}
.admin-subtitle{
    margin-top:12px;
    max-width:760px;
    color:rgba(255,255,255,.82);
}
.admin-actions{
    display:grid;
    gap:12px;
}
.admin-action-card{
    display:flex;
    gap:14px;
    align-items:flex-start;
    padding:16px 18px;
    border-radius:18px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    backdrop-filter:blur(6px);
}
.admin-action-card .material-symbols-outlined{
    font-size:28px;
}
.admin-action-title{
    font-weight:800;
}
.admin-action-copy{
    font-size:14px;
    color:rgba(255,255,255,.78);
    margin-top:4px;
}
.admin-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.admin-alert--success{
    background:#ecfdf5;
    color:#166534;
    border-color:#bbf7d0;
}
.admin-alert--error{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}
.metric-card,
.panel-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.metric-label{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
    font-weight:800;
}
.metric-value{
    margin-top:10px;
    font-size:34px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.metric-copy{
    margin-top:10px;
    font-size:14px;
    color:#64748b;
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
}
.panel-title{
    font-size:22px;
    font-weight:900;
    color:#163750;
    margin-top:6px;
}
.soft-card,
.application-item{
    border:1px solid #e2e8f0;
    background:#f8fafc;
    border-radius:18px;
    padding:18px;
}
.soft-card-title,
.application-ref{
    font-weight:800;
    color:#0f172a;
}
.soft-card-copy{
    margin-top:8px;
    color:#64748b;
    font-size:14px;
    line-height:1.5;
}
.role-pill,
.tag-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    font-weight:700;
    background:#e6eef5;
    color:#234E70;
    text-transform:capitalize;
}
.application-item{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
}
.application-name{
    margin-top:4px;
    font-weight:700;
    color:#1e293b;
}
.application-meta{
    margin-top:4px;
    font-size:13px;
    color:#64748b;
}
.tag-pill{
    background:#f1f5f9;
    color:#334155;
}
@media (min-width: 1024px){
    .admin-hero{
        grid-template-columns:1.4fr .9fr;
        align-items:end;
    }
}
</style>

@endsection
