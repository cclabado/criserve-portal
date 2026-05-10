@extends('layouts.app')

@section('content')

@php
    $isReportingOfficer = auth()->user()?->role === 'reporting_officer';
    $dashboardRoute = $isReportingOfficer ? route('reporting.dashboard') : route('admin.dashboard');
    $reportsRoute = $isReportingOfficer ? route('reporting.reports') : route('admin.reports');
    $appliedFilterCount = collect([
        ($filters['status'] ?? 'all') !== 'all',
        ! empty($filters['assistance_type_id']),
        ! empty($filters['assistance_subtype_id']),
        ! empty($filters['mode_of_assistance_id']),
        ! empty($filters['service_provider_id']),
        ! empty($filters['social_worker_id']),
        ! empty($filters['approving_officer_id']),
        ($filters['service_point'] ?? 'all') !== 'all',
        ($filters['client_sector'] ?? 'all') !== 'all',
        ($filters['client_sub_category'] ?? 'all') !== 'all',
        ($filters['sex'] ?? 'all') !== 'all',
        ! is_null($filters['min_amount']),
        ! is_null($filters['max_amount']),
    ])->filter()->count();

    $topStatus = $statusBreakdown->sortDesc()->keys()->first();
@endphp

<main class="space-y-6">

    <section class="reports-hero">
        <div>
            <p class="reports-kicker">{{ $isReportingOfficer ? 'Reporting Officer' : 'Administrator' }}</p>
            <h1 class="reports-title">Report Generation</h1>
            <p class="reports-copy">
                Build daily, monthly, yearly, or custom reports with cleaner filters and a quick operational summary.
            </p>
        </div>

        <div class="reports-hero__meta">
            <div class="reports-badge">
                <span class="material-symbols-outlined">calendar_month</span>
                <div>
                    <p class="reports-badge__label">Coverage</p>
                    <p class="reports-badge__value">
                        {{ $filters['date_from'] ?: 'Start date' }} to {{ $filters['date_to'] ?: 'End date' }}
                    </p>
                </div>
            </div>

            <div class="reports-badge">
                <span class="material-symbols-outlined">filter_alt</span>
                <div>
                    <p class="reports-badge__label">Active Filters</p>
                    <p class="reports-badge__value">{{ number_format($appliedFilterCount) }}</p>
                </div>
            </div>

            <div class="reports-hero__actions">
                <a href="{{ $dashboardRoute }}" class="btn-ghost inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Dashboard
                </a>

                <a href="{{ $reportsRoute }}?{{ http_build_query(array_merge(request()->query(), ['format' => 'csv'])) }}"
                   class="btn-primary inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Download CSV
                </a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="reports-alert reports-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="reports-alert reports-alert--error">
            <p class="font-semibold">Please review the report filters.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="panel-head panel-head--stacked">
            <div>
                <p class="panel-kicker">Filters</p>
                <h2 class="panel-title">Build Report</h2>
                <p class="panel-copy">Start with the reporting period, then narrow the results only if needed.</p>
            </div>
        </div>

        <form method="GET" action="{{ $reportsRoute }}" class="space-y-6 mt-6">
            <input type="hidden" name="report_type" value="custom">

            <div class="filter-band">
                <div>
                    <label class="label">Date From</label>
                    <input type="date" name="date_from" class="input" value="{{ $filters['date_from'] }}">
                </div>

                <div>
                    <label class="label">Date To</label>
                    <input type="date" name="date_to" class="input" value="{{ $filters['date_to'] }}">
                </div>

                <div>
                    <label class="label">Status</label>
                    <select name="status" class="input">
                        <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                        @foreach($options['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filter-columns">
                <section class="soft-panel">
                    <div class="soft-panel__head">
                        <p class="soft-panel__eyebrow">Case Filters</p>
                        <h3 class="soft-panel__title">Assistance and workflow</h3>
                    </div>

                    <div class="filter-grid">
                        <div>
                            <label class="label">Assistance Type</label>
                            <select name="assistance_type_id" class="input">
                                <option value="">All types</option>
                                @foreach($options['assistanceTypes'] as $type)
                                    <option value="{{ $type->id }}" @selected((string) $filters['assistance_type_id'] === (string) $type->id)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Assistance Subtype</label>
                            <select name="assistance_subtype_id" class="input">
                                <option value="">All subtypes</option>
                                @foreach($options['assistanceSubtypes'] as $subtype)
                                    <option value="{{ $subtype->id }}" @selected((string) $filters['assistance_subtype_id'] === (string) $subtype->id)>
                                        {{ $subtype->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Mode of Assistance</label>
                            <select name="mode_of_assistance_id" class="input">
                                <option value="">All modes</option>
                                @foreach($options['modesOfAssistance'] as $mode)
                                    <option value="{{ $mode->id }}" @selected((string) $filters['mode_of_assistance_id'] === (string) $mode->id)>
                                        {{ $mode->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Service Provider</label>
                            <select name="service_provider_id" class="input">
                                <option value="">All providers</option>
                                @foreach($options['serviceProviders'] as $provider)
                                    <option value="{{ $provider->id }}" @selected((string) $filters['service_provider_id'] === (string) $provider->id)>
                                        {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Service Point</label>
                            <select name="service_point" class="input">
                                <option value="all" @selected($filters['service_point'] === 'all')>All service points</option>
                                @foreach($options['servicePoints'] as $servicePoint)
                                    <option value="{{ $servicePoint }}" @selected($filters['service_point'] === $servicePoint)>
                                        {{ $servicePoint }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Minimum Amount</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="min_amount"
                                   class="input"
                                   value="{{ old('min_amount', $filters['min_amount']) }}"
                                   placeholder="0.00">
                        </div>

                        <div>
                            <label class="label">Maximum Amount</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="max_amount"
                                   class="input"
                                   value="{{ old('max_amount', $filters['max_amount']) }}"
                                   placeholder="0.00">
                        </div>
                    </div>
                </section>

                <section class="soft-panel">
                    <div class="soft-panel__head">
                        <p class="soft-panel__eyebrow">Client and Staff</p>
                        <h3 class="soft-panel__title">People-centered filters</h3>
                    </div>

                    <div class="filter-grid">
                        <div>
                            <label class="label">Client Sector</label>
                            <select name="client_sector" class="input">
                                <option value="all" @selected($filters['client_sector'] === 'all')>All sectors</option>
                                @foreach($options['clientSectors'] as $sector)
                                    <option value="{{ $sector }}" @selected($filters['client_sector'] === $sector)>
                                        {{ $sector }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Client Sub Category</label>
                            <select name="client_sub_category" class="input">
                                <option value="all" @selected($filters['client_sub_category'] === 'all')>All sub categories</option>
                                @foreach($options['clientSubCategories'] as $subCategory)
                                    <option value="{{ $subCategory }}" @selected($filters['client_sub_category'] === $subCategory)>
                                        {{ $subCategory }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Sex</label>
                            <select name="sex" class="input">
                                <option value="all" @selected($filters['sex'] === 'all')>All sexes</option>
                                @foreach($options['sexes'] as $sex)
                                    <option value="{{ $sex }}" @selected($filters['sex'] === $sex)>
                                        {{ $sex }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Social Worker</label>
                            <select name="social_worker_id" class="input">
                                <option value="">All social workers</option>
                                @foreach($options['socialWorkers'] as $socialWorker)
                                    <option value="{{ $socialWorker->id }}" @selected((string) $filters['social_worker_id'] === (string) $socialWorker->id)>
                                        {{ $socialWorker->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Approving Officer</label>
                            <select name="approving_officer_id" class="input">
                                <option value="">All approving officers</option>
                                @foreach($options['approvingOfficers'] as $approvingOfficer)
                                    <option value="{{ $approvingOfficer->id }}" @selected((string) $filters['approving_officer_id'] === (string) $approvingOfficer->id)>
                                        {{ $approvingOfficer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">assessment</span>
                    Generate Report
                </button>

                <a href="{{ $reportsRoute }}" class="btn-secondary inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="metric-card">
            <p class="metric-label">Applications</p>
            <p class="metric-value">{{ number_format($summary['total_applications']) }}</p>
            <p class="metric-copy">Records matching the current report scope.</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">Released and Approved</p>
            <p class="metric-value">{{ number_format($summary['released'] + $summary['approved']) }}</p>
            <p class="metric-copy">Cases that already reached a positive outcome.</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">For Approval</p>
            <p class="metric-value">{{ number_format($summary['for_approval']) }}</p>
            <p class="metric-copy">Applications still waiting in the pipeline.</p>
        </article>

        <article class="metric-card metric-card--accent">
            <p class="metric-label">Total Final Amount</p>
            <p class="metric-value">PHP {{ number_format($summary['total_amount'], 2) }}</p>
            <p class="metric-copy">Based on final approved or encoded amounts.</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr,.8fr]">
        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Overview</p>
                    <h2 class="panel-title">Breakdowns at a Glance</h2>
                </div>
            </div>

            <div class="breakdown-grid mt-6">
                <article class="breakdown-card">
                    <div class="breakdown-card__head">
                        <h3 class="breakdown-card__title">By Status</h3>
                        <span class="breakdown-card__meta">{{ $statusBreakdown->sum() }} total</span>
                    </div>

                    <div class="space-y-3 mt-4">
                        @forelse($statusBreakdown as $label => $total)
                            @php
                                $statusPercent = $summary['total_applications'] > 0 ? round(($total / $summary['total_applications']) * 100) : 0;
                            @endphp
                            <div class="stat-row">
                                <div class="stat-row__head">
                                    <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                                    <span>{{ number_format($total) }}</span>
                                </div>
                                <div class="stat-bar">
                                    <span style="width: {{ max($statusPercent, $total > 0 ? 8 : 0) }}%"></span>
                                </div>
                            </div>
                        @empty
                            <p class="empty-copy">No status data available for the selected filters.</p>
                        @endforelse
                    </div>
                </article>

                <article class="breakdown-card">
                    <div class="breakdown-card__head">
                        <h3 class="breakdown-card__title">By Assistance Type</h3>
                        <span class="breakdown-card__meta">Top 6</span>
                    </div>

                    <div class="token-list mt-4">
                        @forelse($typeBreakdown as $label => $total)
                            <span class="token-item">{{ $label }} <strong>{{ number_format($total) }}</strong></span>
                        @empty
                            <p class="empty-copy">No assistance-type breakdown yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="breakdown-card">
                    <div class="breakdown-card__head">
                        <h3 class="breakdown-card__title">By Client Sector</h3>
                        <span class="breakdown-card__meta">Top 6</span>
                    </div>

                    <div class="token-list mt-4">
                        @forelse($sectorBreakdown as $label => $total)
                            <span class="token-item">{{ $label }} <strong>{{ number_format($total) }}</strong></span>
                        @empty
                            <p class="empty-copy">No sector breakdown yet.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>

        <aside class="panel-card panel-card--tall">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Highlights</p>
                    <h2 class="panel-title">Quick Reading</h2>
                </div>
            </div>

            <div class="insight-stack mt-6">
                <article class="insight-card">
                    <p class="insight-card__label">Most Common Status</p>
                    <p class="insight-card__value">
                        {{ $topStatus ? ucwords(str_replace('_', ' ', $topStatus)) : 'No data yet' }}
                    </p>
                </article>

                <article class="insight-card">
                    <p class="insight-card__label">Recommended Amount</p>
                    <p class="insight-card__value">PHP {{ number_format($summary['recommended_amount'], 2) }}</p>
                </article>

                <article class="insight-card">
                    <p class="insight-card__label">Amount Needed</p>
                    <p class="insight-card__value">PHP {{ number_format($summary['amount_needed'], 2) }}</p>
                </article>

            </div>
        </aside>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <p class="panel-kicker">Results</p>
                <h2 class="panel-title">Detailed Applications</h2>
            </div>
        </div>

        <div class="table-wrap mt-6">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Assistance</th>
                        <th>Sector</th>
                        <th>Final Amount</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>
                                <p class="table-name">{{ $application->reference_no ?: 'No reference' }}</p>
                                <p class="table-subtitle">{{ $application->gis_visit_type ?: 'No service point' }}</p>
                            </td>
                            <td>
                                <p class="table-name">
                                    {{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: 'No client name' }}
                                </p>
                                <p class="table-subtitle">{{ $application->client_sector ?: 'No sector' }}</p>
                            </td>
                            <td>
                                <span class="status-pill">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span>
                            </td>
                            <td>
                                <p class="table-name">{{ $application->assistanceType?->name ?: 'No type' }}</p>
                                <p class="table-subtitle">{{ $application->assistanceSubtype?->name ?: 'No subtype' }}</p>
                            </td>
                            <td>{{ $application->client_sub_category ?: '-' }}</td>
                            <td>PHP {{ number_format((float) ($application->final_amount ?? 0), 2) }}</td>
                            <td>{{ optional($application->created_at)->format('Y-m-d') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-500">No applications matched the current report filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $applications->links() }}
        </div>
    </section>

</main>

<style>
.reports-hero{
    display:grid;
    gap:22px;
    padding:30px;
    border-radius:26px;
    color:#fff;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 30%),
        radial-gradient(circle at bottom left, rgba(120,177,214,.35), transparent 35%),
        linear-gradient(135deg, #163750 0%, #1f5377 55%, #2b739d 100%);
    box-shadow:0 20px 40px rgba(15, 35, 52, .16);
}
.reports-kicker,
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
}
.reports-kicker{
    color:rgba(255,255,255,.72);
}
.reports-title{
    margin-top:8px;
    font-size:34px;
    line-height:1.04;
    font-weight:900;
}
.reports-copy{
    margin-top:12px;
    max-width:760px;
    color:rgba(255,255,255,.84);
}
.reports-hero__meta{
    display:grid;
    gap:14px;
}
.reports-badge{
    display:flex;
    gap:12px;
    align-items:center;
    padding:16px 18px;
    border-radius:18px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.16);
    backdrop-filter:blur(6px);
}
.reports-badge .material-symbols-outlined{
    font-size:24px;
}
.reports-badge__label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:rgba(255,255,255,.68);
}
.reports-badge__value{
    font-size:16px;
    font-weight:800;
    margin-top:2px;
}
.reports-hero__actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}
.reports-alert{
    border-radius:18px;
    padding:16px 18px;
    border:1px solid transparent;
}
.reports-alert--success{
    background:#edf8f1;
    border-color:#b8e3c7;
    color:#1e5d3a;
}
.reports-alert--error{
    background:#fef2f2;
    border-color:#fecaca;
    color:#991b1b;
}
.panel-card{
    background:#fff;
    border:1px solid #d9e4f0;
    border-radius:24px;
    padding:28px;
    box-shadow:0 16px 40px rgba(15, 35, 52, .06);
}
.panel-card--tall{
    height:100%;
}
.panel-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
}
.panel-head--stacked{
    align-items:flex-start;
}
.panel-kicker{
    color:#5c7794;
}
.panel-title{
    margin-top:8px;
    font-size:32px;
    line-height:1.05;
    font-weight:900;
    color:#133b5c;
}
.panel-copy{
    margin-top:10px;
    color:#5f7488;
}
.filter-band{
    display:grid;
    gap:16px;
    padding:18px;
    border-radius:22px;
    border:1px solid #d9e4f0;
    background:linear-gradient(180deg, #f8fbfe 0%, #f3f7fb 100%);
}
.filter-columns{
    display:grid;
    gap:18px;
}
.soft-panel{
    border:1px solid #e1eaf2;
    border-radius:22px;
    padding:20px;
    background:#fcfdff;
}
.soft-panel__head{
    margin-bottom:16px;
}
.soft-panel__eyebrow{
    font-size:11px;
    font-weight:800;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:#7290ad;
}
.soft-panel__title{
    margin-top:8px;
    font-size:22px;
    line-height:1.1;
    font-weight:850;
    color:#173f61;
}
.filter-grid{
    display:grid;
    gap:16px;
}
.label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    font-weight:700;
    color:#254b6a;
}
.input{
    width:100%;
    border:1px solid #cad8e6;
    border-radius:18px;
    padding:14px 16px;
    background:#fff;
    color:#0f2740;
    transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.input:focus{
    outline:none;
    border-color:#2f6a91;
    box-shadow:0 0 0 4px rgba(47, 106, 145, .12);
}
.filter-actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}
.btn-primary,
.btn-secondary,
.btn-ghost{
    min-height:48px;
    padding:0 18px;
    border-radius:16px;
    font-weight:800;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.btn-primary{
    color:#fff;
    background:linear-gradient(135deg, #1b557b 0%, #2b739d 100%);
    box-shadow:0 14px 26px rgba(31, 83, 119, .18);
}
.btn-secondary{
    color:#173f61;
    border:1px solid #cad8e6;
    background:#f7fafc;
}
.btn-ghost{
    color:#fff;
    border:1px solid rgba(255,255,255,.28);
    background:rgba(255,255,255,.08);
}
.btn-primary:hover,
.btn-secondary:hover,
.btn-ghost:hover{
    transform:translateY(-1px);
}
.metric-card{
    background:#fff;
    border:1px solid #d9e4f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 32px rgba(15, 35, 52, .05);
}
.metric-card--accent{
    background:linear-gradient(180deg, #f4fbff 0%, #eef7fd 100%);
}
.metric-label{
    font-size:12px;
    font-weight:800;
    letter-spacing:.15em;
    text-transform:uppercase;
    color:#67829c;
}
.metric-value{
    margin-top:10px;
    font-size:28px;
    line-height:1.1;
    font-weight:900;
    color:#133b5c;
}
.metric-copy{
    margin-top:10px;
    color:#5f7488;
    font-size:14px;
}
.breakdown-grid{
    display:grid;
    gap:18px;
}
.breakdown-card{
    border:1px solid #e0e9f1;
    border-radius:22px;
    padding:20px;
    background:#fcfdff;
}
.breakdown-card__head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
}
.breakdown-card__title{
    font-size:18px;
    font-weight:850;
    color:#173f61;
}
.breakdown-card__meta{
    font-size:13px;
    color:#6c859d;
}
.stat-row__head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:14px;
    color:#234b6a;
    font-weight:700;
}
.stat-bar{
    margin-top:8px;
    width:100%;
    height:10px;
    background:#e9f0f6;
    border-radius:999px;
    overflow:hidden;
}
.stat-bar span{
    display:block;
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #1c577e 0%, #66a9cf 100%);
}
.token-list{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.token-item{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 12px;
    border-radius:999px;
    background:#eef5fb;
    color:#194466;
    font-size:14px;
}
.empty-copy{
    font-size:14px;
    color:#7187a0;
}
.insight-stack{
    display:grid;
    gap:14px;
}
.insight-card{
    border:1px solid #e0e9f1;
    border-radius:20px;
    padding:18px;
    background:#fcfdff;
}
.insight-card--soft{
    background:linear-gradient(180deg, #f8fbfe 0%, #f3f7fb 100%);
}
.insight-card__label{
    font-size:12px;
    font-weight:800;
    letter-spacing:.14em;
    text-transform:uppercase;
    color:#6e87a0;
}
.insight-card__value{
    margin-top:8px;
    font-size:24px;
    line-height:1.1;
    font-weight:900;
    color:#143b5b;
}
.insight-card__copy{
    margin-top:8px;
    color:#5f7488;
    line-height:1.6;
}
.table-wrap{
    overflow:auto;
}
.admin-table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}
.admin-table th{
    text-align:left;
    padding:0 12px 16px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:#69829b;
}
.admin-table td{
    padding:18px 12px;
    border-top:1px solid #e5edf5;
    vertical-align:top;
    color:#173a5a;
}
.table-name{
    font-weight:850;
    color:#143b5b;
}
.table-subtitle{
    margin-top:4px;
    font-size:14px;
    color:#6d8298;
}
.status-pill{
    display:inline-flex;
    align-items:center;
    padding:8px 12px;
    border-radius:999px;
    background:#e9f2f9;
    color:#1f5377;
    font-weight:800;
    font-size:13px;
}
@media (min-width: 768px){
    .reports-hero{
        grid-template-columns:minmax(0, 1.3fr) minmax(320px, .9fr);
        align-items:end;
    }
    .filter-band{
        grid-template-columns:repeat(4, minmax(0, 1fr));
    }
    .filter-columns{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
    .filter-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
    .breakdown-grid{
        grid-template-columns:repeat(3, minmax(0, 1fr));
    }
}
</style>

@endsection
