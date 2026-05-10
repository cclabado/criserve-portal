@extends('layouts.app')

@section('content')

@php
    $appliedFilters = collect([
        $filters['search'] !== '' ? 'Search: '.$filters['search'] : null,
        $filters['action'] !== '' ? 'Action: '.$filters['action'] : null,
        $filters['auditable_type'] !== '' ? 'Record: '.class_basename($filters['auditable_type']) : null,
        $filters['user_id'] !== '' ? 'Actor selected' : null,
        $filters['date_from'] !== '' ? 'From: '.$filters['date_from'] : null,
        $filters['date_to'] !== '' ? 'To: '.$filters['date_to'] : null,
    ])->filter()->values();

    $visibleCount = $logs->count();
    $totalCount = $logs->total();
    $latestLog = $logs->first();
@endphp

<main class="space-y-6">
    <section class="audit-hero">
        <div>
            <p class="audit-kicker">Administrator</p>
            <h1 class="audit-title">Audit Trail Workspace</h1>
            <p class="audit-copy">
                Track who did what, which record changed, and the exact request trail behind every transaction.
            </p>
        </div>

        <div class="audit-hero__actions">
            <div class="audit-badge">
                <span class="material-symbols-outlined">manage_search</span>
                <div>
                    <p class="audit-badge__label">Visible Logs</p>
                    <p class="audit-badge__value">{{ number_format($visibleCount) }}</p>
                </div>
            </div>

            <div class="audit-badge">
                <span class="material-symbols-outlined">inventory_2</span>
                <div>
                    <p class="audit-badge__label">Matching Records</p>
                    <p class="audit-badge__value">{{ number_format($totalCount) }}</p>
                </div>
            </div>

            <a href="{{ route('admin.dashboard') }}" class="audit-back">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to Dashboard
            </a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="audit-stat">
            <p class="audit-stat__label">Latest Activity</p>
            <p class="audit-stat__value audit-stat__value--compact">{{ $latestLog?->action ?: 'No logs yet' }}</p>
            <p class="audit-stat__copy">{{ $latestLog?->created_at?->format('M d, Y h:i A') ?: 'Waiting for first transaction' }}</p>
        </article>

        <article class="audit-stat">
            <p class="audit-stat__label">Active Filters</p>
            <p class="audit-stat__value">{{ number_format($appliedFilters->count()) }}</p>
            <p class="audit-stat__copy">{{ $appliedFilters->isEmpty() ? 'Showing the full trail.' : 'Filtered investigative view.' }}</p>
        </article>

        <article class="audit-stat">
            <p class="audit-stat__label">Actors in View</p>
            <p class="audit-stat__value">{{ number_format($logs->pluck('user_id')->filter()->unique()->count()) }}</p>
            <p class="audit-stat__copy">Distinct users appearing on this page.</p>
        </article>
    </section>

    <section class="panel-card">
        <div class="panel-head panel-head--stacked">
            <div>
                <p class="panel-kicker">Filters</p>
                <h2 class="panel-title">Refine the Trail</h2>
                <p class="panel-copy">Narrow by action, actor, record type, or date window to investigate a transaction quickly.</p>
            </div>
        </div>

        @if($appliedFilters->isNotEmpty())
            <div class="audit-chip-row mt-5">
                @foreach($appliedFilters as $chip)
                    <span class="audit-chip">{{ $chip }}</span>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('admin.audit-logs') }}" class="audit-filter-grid mt-6">
            <div class="md:col-span-2">
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Action, model, name, or email">
            </div>

            <div>
                <label class="label">Action</label>
                <select name="action" class="input">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Record Type</label>
                <select name="auditable_type" class="input">
                    <option value="">All record types</option>
                    @foreach($auditableTypes as $type)
                        <option value="{{ $type }}" @selected($filters['auditable_type'] === $type)>{{ class_basename($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Actor</label>
                <select name="user_id" class="input">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($filters['user_id'] === (string) $user->id)>{{ $user->name ?: $user->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Date From</label>
                <input type="date" name="date_from" class="input" value="{{ $filters['date_from'] }}">
            </div>

            <div>
                <label class="label">Date To</label>
                <input type="date" name="date_to" class="input" value="{{ $filters['date_to'] }}">
            </div>

            <div class="md:col-span-4 flex flex-wrap gap-3 justify-end">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route('admin.audit-logs') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <p class="panel-kicker">Timeline</p>
                <h2 class="panel-title">Recent Audit Logs</h2>
                <p class="panel-copy">Each row shows actor, action, target record, request context, and captured field-level changes.</p>
            </div>
        </div>

        <div class="table-wrap mt-6">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Request Trail</th>
                        <th>Change Set</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $before = $log->metadata['before'] ?? [];
                            $after = $log->metadata['after'] ?? [];
                            $method = strtoupper((string) ($log->metadata['method'] ?? '-'));
                            $methodClass = match ($method) {
                                'POST' => 'method-pill--post',
                                'PATCH' => 'method-pill--patch',
                                'PUT' => 'method-pill--put',
                                'DELETE' => 'method-pill--delete',
                                default => 'method-pill--default',
                            };
                            $actionClass = str_contains($log->action, 'deleted') || str_contains($log->action, 'denied')
                                ? 'action-pill--danger'
                                : (str_contains($log->action, 'created') || str_contains($log->action, 'approved') || str_contains($log->action, 'released')
                                    ? 'action-pill--success'
                                    : 'action-pill--neutral');
                        @endphp
                        <tr>
                            <td>
                                <p class="table-name">{{ $log->created_at?->format('M d, Y') }}</p>
                                <p class="table-subtitle">{{ $log->created_at?->format('h:i:s A') }}</p>
                            </td>
                            <td>
                                <div class="actor-card">
                                    <div class="actor-avatar">
                                        {{ strtoupper(substr($log->user?->name ?: 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="table-name">{{ $log->user?->name ?: 'System / Unknown' }}</p>
                                        <p class="table-subtitle">{{ $log->user?->email ?: 'No linked email' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="action-pill {{ $actionClass }}">{{ $log->action }}</span>
                            </td>
                            <td>
                                <div class="record-card">
                                    <p class="table-name">{{ $log->auditable_type ? class_basename($log->auditable_type) : 'General Event' }}</p>
                                    <p class="table-subtitle">ID: {{ $log->auditable_id ?: 'N/A' }}</p>
                                </div>
                            </td>
                            <td>
                                <div class="request-card">
                                    <span class="method-pill {{ $methodClass }}">{{ $method }}</span>
                                    <p class="request-path">{{ $log->metadata['path'] ?? '-' }}</p>
                                    <p class="table-subtitle">{{ $log->metadata['route_name'] ?? 'No route name' }}</p>
                                    <p class="table-subtitle">{{ $log->ip_address ?: 'No IP captured' }}</p>
                                </div>
                            </td>
                            <td>
                                @if($before || $after)
                                    <details class="change-box">
                                        <summary class="change-box__summary">
                                            <span>Inspect Changes</span>
                                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                        </summary>

                                        @if($before)
                                            <div class="change-panel">
                                                <p class="change-panel__label">Before</p>
                                                <pre class="change-panel__body">{{ json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif

                                        @if($after)
                                            <div class="change-panel">
                                                <p class="change-panel__label">After</p>
                                                <pre class="change-panel__body">{{ json_encode($after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                    </details>
                                @else
                                    <span class="empty-copy">No field diff captured</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-10">No audit logs matched the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    </section>
</main>

<style>
.audit-hero,.panel-card,.audit-stat{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.audit-hero{
    padding:28px 30px;
    display:grid;
    gap:24px;
    background:
        radial-gradient(circle at top right, rgba(164, 214, 219, .36), transparent 34%),
        linear-gradient(135deg, #ffffff 0%, #edf7f6 100%);
}
.audit-kicker,.panel-kicker,.audit-stat__label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.audit-title,.panel-title{
    color:#163750;
    font-weight:900;
}
.audit-title{
    font-size:34px;
    margin-top:10px;
}
.audit-copy,.panel-copy,.audit-stat__copy{
    margin-top:10px;
    color:#64748b;
}
.audit-hero__actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:stretch;
}
.audit-badge,.audit-back,.btn-primary,.btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:12px 16px;
    border-radius:16px;
    font-weight:700;
}
.audit-badge{
    background:#f8fbfe;
    color:#163750;
    border:1px solid #d9e6f0;
    min-width:180px;
    justify-content:flex-start;
}
.audit-badge__label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
}
.audit-badge__value{
    font-size:18px;
    font-weight:900;
    color:#163750;
}
.audit-back,.btn-primary{
    background:#234E70;
    color:#fff;
}
.btn-secondary{
    background:#e6eef5;
    color:#234E70;
}
.audit-stat,.panel-card{
    padding:22px;
}
.audit-stat__value{
    margin-top:10px;
    font-size:30px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.audit-stat__value--compact{
    font-size:18px;
    line-height:1.25;
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
}
.panel-head--stacked{
    flex-direction:column;
}
.audit-chip-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.audit-chip{
    display:inline-flex;
    align-items:center;
    padding:8px 12px;
    border-radius:999px;
    background:#eff6ff;
    color:#1d4ed8;
    font-size:12px;
    font-weight:700;
}
.audit-filter-grid{
    display:grid;
    gap:16px;
}
@media (min-width: 768px){
    .audit-filter-grid{
        grid-template-columns:repeat(4, minmax(0, 1fr));
    }
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
.input{
    appearance:none;
    border:1px solid #d4dde6;
    border-radius:14px;
    padding:12px 14px;
    width:100%;
    background:#fff;
}
.table-wrap{
    overflow:auto;
}
.audit-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}
.audit-table th{
    padding:14px 12px;
    text-align:left;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
    border-bottom:1px solid #e5e7eb;
    background:#f8fbfe;
    position:sticky;
    top:0;
    z-index:1;
}
.audit-table td{
    padding:16px 12px;
    border-bottom:1px solid #e5e7eb;
    vertical-align:top;
    font-size:14px;
}
.table-name{
    font-weight:800;
    color:#163750;
}
.table-subtitle{
    margin-top:4px;
    font-size:12px;
    color:#64748b;
}
.actor-card{
    display:flex;
    gap:12px;
    align-items:flex-start;
}
.actor-avatar{
    width:38px;
    height:38px;
    border-radius:999px;
    background:linear-gradient(135deg, #234E70 0%, #5b95bf 100%);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:900;
    flex-shrink:0;
}
.action-pill,.method-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
}
.action-pill--neutral{
    background:#e2e8f0;
    color:#334155;
}
.action-pill--success{
    background:#dcfce7;
    color:#166534;
}
.action-pill--danger{
    background:#fee2e2;
    color:#991b1b;
}
.record-card,.request-card{
    padding:12px 14px;
    border-radius:16px;
    background:#f8fbfe;
    border:1px solid #e2e8f0;
}
.request-path{
    margin-top:10px;
    font-size:13px;
    font-weight:700;
    color:#163750;
    word-break:break-word;
}
.method-pill{
    margin-bottom:8px;
}
.method-pill--post{
    background:#dbeafe;
    color:#1d4ed8;
}
.method-pill--patch{
    background:#fef3c7;
    color:#92400e;
}
.method-pill--put{
    background:#ede9fe;
    color:#6d28d9;
}
.method-pill--delete{
    background:#fee2e2;
    color:#b91c1c;
}
.method-pill--default{
    background:#e2e8f0;
    color:#334155;
}
.change-box{
    border:1px solid #d9e6f0;
    border-radius:18px;
    background:#f8fbfe;
    overflow:hidden;
}
.change-box__summary{
    list-style:none;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 14px;
    font-size:13px;
    font-weight:800;
    color:#1d4ed8;
}
.change-box__summary::-webkit-details-marker{
    display:none;
}
.change-panel{
    padding:0 14px 14px;
}
.change-panel + .change-panel{
    padding-top:4px;
}
.change-panel__label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:#64748b;
    margin-bottom:8px;
}
.change-panel__body{
    margin:0;
    white-space:pre-wrap;
    word-break:break-word;
    font-size:11px;
    line-height:1.5;
    color:#334155;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:12px;
}
.empty-copy{
    font-size:12px;
    color:#94a3b8;
}
@media (max-width: 960px){
    .audit-hero__actions,.panel-head{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

@endsection
