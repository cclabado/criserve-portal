@extends('layouts.app')

@section('content')

@php
    $summary = $selectedRun->summary ?? [
        'total_rows' => 0,
        'clean_count' => 0,
        'duplicate_count' => 0,
        'finding_count' => 0,
        'skipped_count' => 0,
    ];
@endphp

<main
    x-data="{
        selectedRunId: @js($selectedRun->id),
        statusBaseRoute: @js($statusBaseRoute),
        runStatus: @js($selectedRun->status),
        runProgress: @js((int) ($selectedRun->progress_percentage ?? 0)),
        runMessage: @js($selectedRun->progress_message ?? ''),
        runError: @js($selectedRun->error_message ?? ''),
        pollTimer: null,
        isWorking() {
            return ['queued', 'processing'].includes(this.runStatus);
        },
        runStatusLabel() {
            return ({
                queued: 'Queued',
                processing: 'Processing',
                completed: 'Completed',
                failed: 'Failed',
            })[this.runStatus] || 'Not started';
        },
        runStatusClass() {
            return ({
                queued: 'run-status-chip--queued',
                processing: 'run-status-chip--processing',
                completed: 'run-status-chip--completed',
                failed: 'run-status-chip--failed',
            })[this.runStatus] || '';
        },
        startPolling() {
            if (!this.selectedRunId || !this.isWorking()) {
                return;
            }

            this.stopPolling();
            this.pollTimer = window.setInterval(() => this.fetchRunStatus(), 3000);
        },
        stopPolling() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        async fetchRunStatus() {
            if (!this.selectedRunId) {
                return;
            }

            const response = await fetch(this.statusBaseRoute.replace('__RUN__', this.selectedRunId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            this.runStatus = payload.status;
            this.runProgress = payload.progress_percentage || 0;
            this.runMessage = payload.progress_message || '';
            this.runError = payload.error_message || '';

            if (['completed', 'failed'].includes(this.runStatus)) {
                this.stopPolling();
                window.location.reload();
            }
        },
        init() {
            this.startPolling();
            window.addEventListener('beforeunload', () => this.stopPolling(), { once: true });
        },
    }"
    class="space-y-6"
>
    <section class="dedupe-hero">
        <div>
            <p class="dedupe-kicker">{{ $isReportingOfficer ? 'Reporting Officer' : 'Administrator' }}</p>
            <h1 class="dedupe-title">Deduplication Run #{{ $selectedRun->id }}</h1>
            <p class="dedupe-copy">
                Review clean rows, excluded duplicates, and possible duplicate findings for <span class="font-semibold text-slate-700">{{ $selectedRun->original_filename }}</span>.
            </p>
        </div>

        <div class="hero-actions">
            <a href="{{ $indexRoute }}" class="dedupe-ghost">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to Workspace
            </a>
            <a href="{{ $dashboardRoute }}" class="dedupe-back">
                <span class="material-symbols-outlined text-[18px]">space_dashboard</span>
                Dashboard
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="dedupe-alert dedupe-alert--success">{{ session('success') }}</div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="metric-card">
            <p class="metric-label">Uploaded Rows</p>
            <p class="metric-value">{{ number_format($summary['total_rows']) }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Clean Rows</p>
            <p class="metric-value">{{ number_format($summary['clean_count']) }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Duplicates</p>
            <p class="metric-value">{{ number_format($summary['duplicate_count']) }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Findings</p>
            <p class="metric-value">{{ number_format($summary['finding_count']) }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Skipped</p>
            <p class="metric-value">{{ number_format($summary['skipped_count']) }}</p>
        </article>
    </section>

    <section class="space-y-6">
            <section class="panel-card">
                <div class="panel-head">
                    <div>
                        <p class="panel-kicker">Selected Run</p>
                        <h2 class="panel-title">{{ $selectedRun->original_filename }}</h2>
                        <p class="panel-copy">Generated {{ $selectedRun->created_at?->format('M d, Y h:i A') }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="run-status-chip" :class="runStatusClass()" x-text="runStatusLabel()"></span>
                            <span class="text-sm text-slate-500" x-show="runMessage" x-text="runMessage"></span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                            <span class="run-meta-chip">
                                Compare: {{ ($selectedRun->summary['compare_source'] ?? 'system') === 'uploaded_list' ? 'Uploaded list' : 'System database' }}
                            </span>
                            <span class="run-meta-chip">
                                Frequency: {{ !empty($selectedRun->summary['apply_frequency_rules']) ? 'Applied' : 'Not applied' }}
                            </span>
                            @if(!empty($selectedRun->summary['reference_filename']))
                                <span class="run-meta-chip">
                                    Reference file: {{ $selectedRun->summary['reference_filename'] }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($selectedRun->status === 'completed')
                            <a href="{{ route($downloadBaseRoute, [$selectedRun, 'clean'], false) }}" class="btn-primary">Download Clean List</a>
                            <a href="{{ route($downloadBaseRoute, [$selectedRun, 'duplicates'], false) }}" class="btn-secondary">Download Duplicates</a>
                            <a href="{{ route($downloadBaseRoute, [$selectedRun, 'findings'], false) }}" class="btn-secondary">Download Findings</a>
                        @else
                            <span class="btn-secondary btn-secondary--disabled">Downloads unlock after completion</span>
                        @endif
                    </div>
                </div>

                <div class="run-progress-card mt-5" x-cloak>
                    <div class="upload-progress-head">
                        <p class="upload-progress-title">Background Progress</p>
                        <p class="upload-progress-percent" x-text="`${runProgress}%`"></p>
                    </div>
                    <div class="upload-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="runProgress" :aria-valuetext="`${runProgress}%`">
                        <div class="upload-progress-fill" :style="`width: ${runProgress}%`"></div>
                    </div>
                    <p class="upload-progress-copy" x-text="runMessage || 'Waiting for processor update...'"></p>
                    <p class="run-progress-error" x-show="runStatus === 'failed' && runError" x-text="runError"></p>
                </div>
            </section>

            <section class="panel-card">
                <div class="panel-head">
                    <div>
                        <p class="panel-kicker">Clean List</p>
                        <h2 class="panel-title">Eligible Rows</h2>
                    </div>
                </div>

                <div class="table-wrap mt-5">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Name</th>
                                <th>Birthdate</th>
                                <th>Frequency</th>
                                <th>Assistance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($selectedRun->clean_rows ?? []) as $row)
                                <tr>
                                    <td>{{ $row['row_number'] }}</td>
                                    <td>{{ trim($row['last_name'].' '.$row['first_name'].' '.$row['middle_name'].' '.$row['extension_name']) }}</td>
                                    <td>{{ $row['birthdate'] }}</td>
                                    <td>{{ $row['frequency_message'] }}</td>
                                    <td>{{ trim(($row['assistance_subtype'] ?? '').' '.($row['assistance_detail'] ?? '')) ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500">No eligible rows yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel-card">
                <div class="panel-head">
                    <div>
                        <p class="panel-kicker">Duplicates</p>
                        <h2 class="panel-title">Excluded Rows</h2>
                    </div>
                </div>

                <div class="table-wrap mt-5">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Name</th>
                                <th>Reason</th>
                                <th>Source</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($selectedRun->duplicate_rows ?? []) as $row)
                                <tr>
                                    <td>{{ $row['row_number'] }}</td>
                                    <td>{{ trim($row['last_name'].' '.$row['first_name'].' '.$row['middle_name'].' '.$row['extension_name']) }}</td>
                                    <td>{{ $row['duplicate_reason'] }}</td>
                                    <td>{{ $row['matched_source'] ?: '-' }}</td>
                                    <td>{{ $row['basis_reference_no'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500">No duplicate rows recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel-card">
                <div class="panel-head">
                    <div>
                        <p class="panel-kicker">Findings</p>
                        <h2 class="panel-title">Possible Duplicates</h2>
                    </div>
                </div>

                <div class="table-wrap mt-5">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Name</th>
                                <th>Birthdate</th>
                                <th>Finding</th>
                                <th>Possible Matches</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($selectedRun->finding_rows ?? []) as $row)
                                <tr>
                                    <td>{{ $row['row_number'] }}</td>
                                    <td>{{ trim($row['last_name'].' '.$row['first_name'].' '.$row['middle_name'].' '.$row['extension_name']) }}</td>
                                    <td>{{ $row['birthdate'] }}</td>
                                    <td>{{ $row['finding_message'] }}</td>
                                    <td>
                                        @foreach(($row['matches'] ?? []) as $match)
                                            <div class="match-chip">{{ $match['matched_name'] }} | {{ $match['source'] }} | {{ $match['similarity_score'] }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500">No possible duplicate findings yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
    </section>
</main>

<style>
.dedupe-hero,.panel-card,.metric-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.dedupe-hero{
    padding:28px 30px;
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-end;
    background:
        radial-gradient(circle at top left, rgba(184, 220, 244, .55), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #edf5fb 100%);
}
.dedupe-kicker,.panel-kicker,.metric-label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.dedupe-title,.panel-title{
    color:#163750;
    font-weight:900;
}
.dedupe-title{
    font-size:34px;
    margin-top:10px;
}
.dedupe-copy,.panel-copy{
    margin-top:10px;
    color:#64748b;
}
.hero-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.dedupe-back,.dedupe-ghost,.btn-primary,.btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 16px;
    border-radius:14px;
    font-weight:700;
}
.dedupe-back,.btn-primary{
    background:#234E70;
    color:#fff;
}
.dedupe-ghost{
    background:#f8fafc;
    color:#234E70;
}
.btn-secondary{
    background:#e6eef5;
    color:#234E70;
}
.btn-secondary--disabled{
    opacity:.75;
    cursor:default;
}
.panel-card,.metric-card{ padding:22px; }
.metric-value{
    margin-top:10px;
    font-size:30px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
}
.dedupe-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.dedupe-alert--success{ background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
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
.run-meta-chip{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:8px 12px;
    background:#f8fafc;
}
.upload-progress-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.upload-progress-title,.upload-progress-percent{
    font-weight:800;
    color:#163750;
}
.upload-progress-track{
    margin-top:12px;
    height:12px;
    border-radius:999px;
    overflow:hidden;
    background:#dbe7f1;
}
.upload-progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #234E70 0%, #3f83b1 100%);
    transition:width .2s ease;
}
.upload-progress-copy{
    margin-top:10px;
    font-size:13px;
    color:#64748b;
}
.run-progress-card{
    border:1px solid #d9e6f0;
    border-radius:18px;
    padding:16px;
    background:#f8fbfe;
}
.run-progress-error{
    margin-top:10px;
    font-size:13px;
    color:#991b1b;
}
.run-status-chip{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.04em;
}
.run-status-chip--queued{
    background:#e0f2fe;
    color:#075985;
}
.run-status-chip--processing{
    background:#dbeafe;
    color:#1d4ed8;
}
.run-status-chip--completed{
    background:#dcfce7;
    color:#166534;
}
.run-status-chip--failed{
    background:#fee2e2;
    color:#991b1b;
}
.match-chip{
    margin-bottom:6px;
    padding:8px 10px;
    border-radius:999px;
    background:#f8fafc;
    font-size:12px;
    color:#475569;
}
@media (max-width: 960px){
    .dedupe-hero,.panel-head{
        flex-direction:column;
        align-items:flex-start;
    }
    .hero-actions{
        width:100%;
    }
}
</style>

@endsection
