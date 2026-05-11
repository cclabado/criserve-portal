@extends('layouts.app')

@section('content')

@php
    $statusOrder = [
        'submitted',
        'under_review',
        'for_approval',
        'approved',
        'released',
    ];

    $currentStatus = $latestApplication->status ?? null;
    $currentIndex = in_array($currentStatus, $statusOrder, true)
        ? array_search($currentStatus, $statusOrder, true)
        : -1;

    $statusBadgeClasses = match ($latestApplication->status ?? null) {
        'submitted' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'under_review' => 'bg-blue-100 text-blue-700 border border-blue-200',
        'for_approval' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'released' => 'bg-sky-100 text-sky-700 border border-sky-200',
        'cancelled' => 'bg-slate-200 text-slate-700 border border-slate-300',
        'denied' => 'bg-rose-100 text-rose-700 border border-rose-200',
        default => 'bg-slate-100 text-slate-700 border border-slate-200',
    };

    $steps = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'for_approval' => 'For Approval',
        'approved' => 'Approved',
        'released' => 'Released',
    ];

    $breakdownColors = [
        'Submitted' => 'bg-amber-400',
        'Under Review' => 'bg-blue-500',
        'For Approval' => 'bg-indigo-500',
        'Released' => 'bg-emerald-500',
    ];
@endphp

@if(session('success'))
<div class="mx-auto mb-4 max-w-7xl rounded-xl bg-green-100 px-4 py-3 text-green-700">
    {{ session('success') }}
</div>
@endif

<div class="mx-auto max-w-7xl space-y-8 px-8 py-8 bg-surface">

    <section class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-[#0d3550] via-[#184f73] to-[#2a7a93] p-10 text-white shadow-xl">
        <div class="relative z-10 max-w-3xl">
            <span class="inline-flex items-center rounded-full bg-white/15 px-4 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                Client Portal
            </span>
            <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Manage your assistance requests with clarity.</h1>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-white/85 sm:text-base">
                Submit new requests, monitor where your current application stands, and review your past records in one place.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="/client/application" class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-[#123a58] shadow-sm transition hover:bg-slate-100">
                    Apply for Assistance
                </a>
                <a href="{{ route('client.applications') }}" class="inline-flex items-center rounded-xl border border-white/30 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                    View All Applications
                </a>
            </div>
        </div>

        <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 right-28 h-32 w-32 rounded-full bg-cyan-200/20 blur-3xl"></div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total Applications</p>
            <p class="mt-3 text-4xl font-black text-sky-950">{{ $statusSummary['total'] }}</p>
            <p class="mt-2 text-sm text-slate-500">All requests submitted in your account.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Active Requests</p>
            <p class="mt-3 text-4xl font-black text-amber-600">{{ $statusSummary['active'] }}</p>
            <p class="mt-2 text-sm text-slate-500">Cases still moving through review and approval.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Released Assistance</p>
            <p class="mt-3 text-4xl font-black text-emerald-600">{{ $statusSummary['released'] }}</p>
            <p class="mt-2 text-sm text-slate-500">Requests already completed and released.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Closed Requests</p>
            <p class="mt-3 text-4xl font-black text-slate-700">{{ $statusSummary['cancelled'] }}</p>
            <p class="mt-2 text-sm text-slate-500">Applications cancelled or denied after review.</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-6 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Current Tracker</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">Latest Application Status</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Reference No: {{ $latestApplication->reference_no ?? 'No application yet' }}
                    </p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] {{ $statusBadgeClasses }}">
                    {{ $latestApplication ? str_replace('_', ' ', $latestApplication->status) : 'No Application' }}
                </span>
            </div>

            @if(!$latestApplication)
                <div class="py-10 text-center">
                    <p class="text-lg font-semibold text-slate-700">No application submitted yet.</p>
                    <p class="mt-2 text-sm text-slate-500">Start your first request and track its progress here.</p>
                </div>
            @elseif(in_array($currentStatus, ['cancelled', 'denied'], true))
                <div class="mt-6 rounded-2xl border border-slate-300 bg-slate-50 px-6 py-5">
                    <p class="text-sm font-bold uppercase tracking-wide text-slate-700">
                        {{ $currentStatus === 'cancelled' ? 'Application Cancelled' : 'Application Denied' }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">
                        This application is already closed. You may review the full details and submitted records below.
                    </p>
                    @if(!empty($latestApplication->denial_reason))
                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            Reason: {{ $latestApplication->denial_reason }}
                        </p>
                    @endif
                </div>
            @else
                <div class="mt-8">
                    <div class="relative hidden px-3 sm:block">
                        <div class="absolute left-[9%] right-[9%] top-5 h-1 rounded-full bg-slate-200"></div>
                        @if($currentIndex > 0)
                            @php
                                $progressWidth = ((100 / (count($statusOrder) - 1)) * $currentIndex);
                                $activeRailWidth = 82 * ($progressWidth / 100);
                            @endphp
                            <div class="absolute left-[9%] top-5 h-1 rounded-full bg-[#184f73]" style="width: {{ $activeRailWidth }}%;"></div>
                        @endif

                        <div class="relative grid grid-cols-5 gap-3">
                            @foreach($steps as $key => $label)
                                @php
                                    $stepIndex = array_search($key, $statusOrder, true);
                                    $done = $stepIndex <= $currentIndex;
                                    $current = $stepIndex === $currentIndex;
                                @endphp

                                <div class="flex flex-col items-center text-center">
                                    <div class="relative z-10 flex h-11 w-11 items-center justify-center rounded-full border-4 border-white text-sm font-bold {{ $done ? 'bg-[#184f73] text-white shadow-lg shadow-sky-900/15' : 'bg-slate-200 text-slate-500' }}">
                                        @if($done && ! $current)
                                            &#10003;
                                        @else
                                            {{ $stepIndex + 1 }}
                                        @endif
                                    </div>

                                    <p class="mt-4 text-sm font-semibold leading-tight {{ $done ? 'text-sky-900' : 'text-slate-500' }}">
                                        {{ $label }}
                                    </p>

                                    <div class="mt-2 min-h-[1.25rem]">
                                        @if($current)
                                            <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-sky-700">
                                                Current
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4 sm:hidden">
                        @foreach($steps as $key => $label)
                            @php
                                $stepIndex = array_search($key, $statusOrder, true);
                                $done = $stepIndex <= $currentIndex;
                                $current = $stepIndex === $currentIndex;
                            @endphp

                            <div class="flex items-start gap-4 rounded-2xl border px-4 py-4 {{ $current ? 'border-sky-200 bg-sky-50/70' : 'border-slate-200 bg-white' }}">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $done ? 'bg-[#184f73] text-white' : 'bg-slate-200 text-slate-500' }}">
                                    @if($done && ! $current)
                                        &#10003;
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="text-sm font-semibold {{ $done ? 'text-sky-900' : 'text-slate-500' }}">
                                        {{ $label }}
                                    </p>
                                    @if($current)
                                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.14em] text-sky-700">Current stage</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Quick Access</p>
                <h3 class="mt-2 text-xl font-black text-sky-950">What would you like to do?</h3>

                <div class="mt-5 space-y-3">
                    <a href="/client/application" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50">
                        <div>
                            <p class="font-bold text-sky-950">Submit a New Request</p>
                            <p class="text-sm text-slate-500">Open the application form.</p>
                        </div>
                        <span class="material-symbols-outlined text-sky-700">arrow_forward</span>
                    </a>

                    <a href="{{ route('client.applications') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50">
                        <div>
                            <p class="font-bold text-sky-950">Browse My Applications</p>
                            <p class="text-sm text-slate-500">View your complete application history.</p>
                        </div>
                        <span class="material-symbols-outlined text-sky-700">folder_open</span>
                    </a>

                    @if($latestApplication)
                        <a href="{{ route('client.application.show', $latestApplication->id) }}" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50">
                            <div>
                                <p class="font-bold text-sky-950">Open Latest Application</p>
                                <p class="text-sm text-slate-500">{{ $latestApplication->reference_no }}</p>
                            </div>
                            <span class="material-symbols-outlined text-sky-700">visibility</span>
                        </a>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Status Breakdown</p>
                <h3 class="mt-2 text-xl font-black text-sky-950">Your request mix</h3>

                <div class="mt-5 space-y-4">
                    @foreach($statusBreakdown as $label => $count)
                        @php
                            $percent = $statusSummary['total'] > 0 ? round(($count / $statusSummary['total']) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-700">{{ $label }}</span>
                                <span class="text-slate-500">{{ $count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full {{ $breakdownColors[$label] ?? 'bg-slate-400' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">7-Day Trend</p>
                    <h3 class="mt-2 text-2xl font-black text-sky-950">Recent Submissions</h3>
                </div>
                <p class="text-sm text-slate-500">Last 7 days</p>
            </div>

            <div class="mt-8 flex h-72 items-end gap-3">
                @foreach($trendValues as $index => $value)
                    @php
                        $height = $maxTrendValue > 0 ? max(12, round(($value / $maxTrendValue) * 220)) : 12;
                    @endphp
                    <div class="flex flex-1 flex-col items-center justify-end gap-3">
                        <span class="text-xs font-semibold text-slate-500">{{ $value }}</span>
                        <div class="w-full max-w-[56px] rounded-t-2xl bg-gradient-to-t from-[#123a58] to-[#4f9ac5]" style="height: {{ $height }}px"></div>
                        <span class="text-xs font-medium text-slate-500">{{ $trendLabels[$index] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">History</p>
                    <h3 class="mt-2 text-2xl font-black text-sky-950">Recent Records</h3>
                </div>
                <a href="{{ route('client.applications') }}" class="text-sm font-semibold text-sky-700 hover:underline">
                    See all
                </a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($applications->take(4) as $app)
                    <a href="{{ route('client.application.show', $app->id) }}" class="block rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50/60">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-mono text-xs font-bold text-sky-800">{{ $app->reference_no }}</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $app->assistanceType->name ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-500">{{ $app->assistanceSubtype->name ?? 'No subtype selected' }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase
                                @if($app->status == 'submitted') bg-yellow-100 text-yellow-700
                                @elseif($app->status == 'under_review') bg-blue-100 text-blue-700
                                @elseif($app->status == 'for_approval') bg-indigo-100 text-indigo-700
                                @elseif($app->status == 'approved') bg-green-100 text-green-700
                                @elseif($app->status == 'released') bg-green-200 text-green-900
                                @elseif($app->status == 'denied') bg-rose-100 text-rose-700
                                @elseif($app->status == 'cancelled') bg-slate-200 text-slate-700
                                @else bg-gray-100 text-gray-700
                                @endif
                            ">
                                {{ str_replace('_', ' ', $app->status) }}
                            </span>
                        </div>
                        @if($app->client_compliance_status === 'requested')
                            <p class="mt-2 inline-flex rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold uppercase text-amber-800">
                                For Compliance
                            </p>
                        @elseif($app->client_compliance_status === 'resubmitted')
                            <p class="mt-2 inline-flex rounded-full bg-sky-100 px-3 py-1 text-[10px] font-bold uppercase text-sky-800">
                                Compliance Uploaded
                            </p>
                        @endif
                        <p class="mt-3 text-xs text-slate-500">Submitted {{ $app->created_at->format('M d, Y') }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-slate-500">
                        No applications found yet.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-2xl font-black text-sky-950">History & Submissions</h3>
                <p class="text-sm text-on-surface-variant">
                    Review your submitted requests and filter them by status or assistance type.
                </p>
            </div>

            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <select name="status" class="min-w-[180px] px-3 py-2 text-sm border rounded-lg">
                    <option value="">All Status</option>
                    <option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
                    <option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Under Review</option>
                    <option value="for_approval" {{ request('status')=='for_approval'?'selected':'' }}>For Approval</option>
                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                    <option value="released" {{ request('status')=='released'?'selected':'' }}>Released</option>
                    <option value="denied" {{ request('status')=='denied'?'selected':'' }}>Denied</option>
                    <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                </select>

                <select name="type" class="min-w-[200px] px-3 py-2 text-sm border rounded-lg">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-[#123a58] rounded-lg hover:bg-[#0f314b] transition">
                    Apply Filters
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-outline-variant/10 bg-surface-container-lowest shadow-sm">
            <table class="w-full border-collapse text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Reference ID</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Type of Assistance</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Submission Date</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Current Status</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-surface-container">
                    @forelse($applications as $app)
                        <tr class="hover:bg-surface transition-colors">
                            <td class="px-6 py-5">
                                <span class="font-mono text-xs font-bold text-sky-800">
                                    {{ $app->reference_no }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ $app->assistanceType->name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-on-surface-variant">
                                    {{ $app->assistanceSubtype->name ?? '' }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-sm text-on-surface-variant">
                                {{ $app->created_at->format('M d, Y') }}
                            </td>

                            <td class="px-6 py-5">
                                <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase
                                    @if($app->status == 'submitted') bg-yellow-100 text-yellow-700
                                    @elseif($app->status == 'under_review') bg-blue-100 text-blue-700
                                    @elseif($app->status == 'for_approval') bg-indigo-100 text-indigo-700
                                    @elseif($app->status == 'approved') bg-green-100 text-green-700
                                    @elseif($app->status == 'released') bg-green-200 text-green-900
                                    @elseif($app->status == 'denied') bg-rose-100 text-rose-700
                                    @elseif($app->status == 'cancelled') bg-slate-200 text-slate-700
                                    @else bg-gray-100 text-gray-700
                                    @endif
                                ">
                                    {{ str_replace('_',' ', $app->status) }}
                                </span>
                                @if($app->client_compliance_status === 'requested')
                                    <div class="mt-2">
                                        <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-amber-100 text-amber-800">
                                            For Compliance
                                        </span>
                                    </div>
                                @elseif($app->client_compliance_status === 'resubmitted')
                                    <div class="mt-2">
                                        <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-sky-100 text-sky-800">
                                            Compliance Uploaded
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <a href="{{ route('client.application.show', $app->id) }}"
                                   class="text-primary font-bold hover:underline text-sm">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">
                                No applications found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-2">
            {{ $applications->links() }}
        </div>
    </section>

</div>

@endsection
