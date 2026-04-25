@extends('layouts.app')

@section('content')

@php
    $statusPalette = [
        'Submitted' => ['bar' => 'bg-amber-400', 'dot' => 'bg-amber-400'],
        'Under Review' => ['bar' => 'bg-blue-500', 'dot' => 'bg-blue-500'],
        'For Approval' => ['bar' => 'bg-indigo-500', 'dot' => 'bg-indigo-500'],
        'Approved' => ['bar' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
        'Released' => ['bar' => 'bg-teal-500', 'dot' => 'bg-teal-500'],
        'Cancelled' => ['bar' => 'bg-slate-400', 'dot' => 'bg-slate-400'],
    ];
@endphp

<main class="mx-auto max-w-7xl space-y-8 p-6 lg:p-8">

<section class="overflow-hidden rounded-[28px] bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(135deg,_#0B3C5D_0%,_#174A6B_48%,_#245E7A_100%)] px-8 py-9 text-white shadow-[0_24px_60px_rgba(11,60,93,0.18)]">
    <div class="grid gap-8 lg:grid-cols-[1.5fr_.9fr] lg:items-end">
        <div>
            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                Social Worker Dashboard
            </span>

            <h1 class="mt-5 max-w-3xl text-3xl font-bold leading-tight sm:text-4xl">
                Managing welfare cases with clearer priorities and faster daily follow-through.
            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                Review pending applications, monitor your active workload, and spot movement across the last seven days without leaving the dashboard.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-[0.18em] text-white/60">Released This Month</p>
                <div class="mt-3 flex items-end justify-between">
                    <p class="text-4xl font-bold">{{ $releasedThisMonth }}</p>
                    <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-100">
                        Completed cases
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/15 p-4">
                <p class="text-xs uppercase tracking-[0.18em] text-white/60">Team Pulse</p>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    {{ $totalPending }} pending, {{ $urgent }} under review, and {{ $approvedToday }} approved today.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pending</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totalPending }}</p>
                <p class="mt-2 text-xs text-slate-400">Awaiting assessment</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <span class="material-symbols-outlined text-[28px]">pending_actions</span>
            </div>
        </div>
    </article>

    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Approved Today</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $approvedToday }}</p>
                <p class="mt-2 text-xs text-emerald-600">Strong same-day throughput</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <span class="material-symbols-outlined text-[28px]">verified</span>
            </div>
        </div>
    </article>

    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Urgent Reviews</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $urgent }}</p>
                <p class="mt-2 text-xs text-amber-600">Needs immediate attention</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <span class="material-symbols-outlined text-[28px]">priority_high</span>
            </div>
        </div>
    </article>

    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Cases Managed</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totalHandled }}</p>
                <p class="mt-2 text-xs text-slate-400">All recorded applications</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                <span class="material-symbols-outlined text-[28px]">folder_managed</span>
            </div>
        </div>
    </article>

    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">My Assessed Cases</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $myHandled }}</p>
                <p class="mt-2 text-xs text-indigo-600">Personally handled workload</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                <span class="material-symbols-outlined text-[28px]">badge</span>
            </div>
        </div>
    </article>
</section>

<section class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Weekly Intake</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Application Trend</h2>
                <p class="mt-1 text-sm text-slate-500">New submissions recorded over the last 7 days.</p>
            </div>

            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Peak Day Volume</p>
                <p class="mt-2 text-2xl font-bold text-[#0B3C5D]">{{ $maxDailyIntake }}</p>
            </div>
        </div>

        <div class="mt-8">
            <div class="flex h-72 items-end gap-3 sm:gap-4">
                @foreach($dailyIntakes as $index => $count)
                    @php
                        $height = max(($count / $maxDailyIntake) * 100, $count > 0 ? 16 : 8);
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-3">
                        <div class="flex h-full w-full items-end">
                            <div class="relative w-full rounded-t-[20px] bg-gradient-to-t from-[#0B3C5D] via-[#2A6584] to-[#7FC4D9] shadow-[0_12px_28px_rgba(11,60,93,0.18)] transition hover:-translate-y-1"
                                 style="height: {{ $height }}%;">
                                <span class="absolute -top-8 left-1/2 -translate-x-1/2 text-xs font-bold text-slate-500">
                                    {{ $count }}
                                </span>
                            </div>
                        </div>
                        <p class="text-center text-xs font-medium text-slate-500">{{ $trendLabels[$index] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Current Mix</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Status Breakdown</h2>
        <p class="mt-1 text-sm text-slate-500">Live view of where applications currently sit in the process.</p>

        <div class="mt-6 space-y-4">
            @foreach($statusBreakdown as $label => $count)
                @php
                    $maxBreakdown = max($statusBreakdown) ?: 1;
                    $width = $count > 0 ? max(($count / $maxBreakdown) * 100, 8) : 0;
                @endphp
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $statusPalette[$label]['dot'] ?? 'bg-slate-400' }}"></span>
                            <span class="font-medium text-slate-600">{{ $label }}</span>
                        </div>
                        <span class="font-bold text-slate-900">{{ $count }}</span>
                    </div>

                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $statusPalette[$label]['bar'] ?? 'bg-slate-400' }}"
                             style="width: {{ $width }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Recent Queue</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Latest Applications</h2>
            <p class="mt-1 text-sm text-slate-500">Quick scan of the newest cases entering the workflow.</p>
        </div>

        <a href="{{ route('socialworker.applications') }}"
           class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            Open Application Queue
        </a>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-left">
            <thead>
                <tr class="border-b border-slate-200 text-xs uppercase tracking-[0.18em] text-slate-400">
                    <th class="pb-3 pr-6 font-semibold">Reference</th>
                    <th class="pb-3 pr-6 font-semibold">Applicant</th>
                    <th class="pb-3 pr-6 font-semibold">Assistance</th>
                    <th class="pb-3 pr-6 font-semibold">Status</th>
                    <th class="pb-3 font-semibold">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($recentApplications as $app)
                    <tr class="transition hover:bg-slate-50/80">
                        <td class="py-4 pr-6 font-semibold text-[#0B3C5D]">{{ $app->reference_no }}</td>
                        <td class="py-4 pr-6 text-slate-700">{{ $app->client?->first_name }} {{ $app->client?->last_name }}</td>
                        <td class="py-4 pr-6 text-slate-600">{{ $app->assistanceType?->name ?? 'N/A' }}</td>
                        <td class="py-4 pr-6">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase
                                @if($app->status === 'submitted') bg-amber-100 text-amber-700
                                @elseif($app->status === 'under_review') bg-blue-100 text-blue-700
                                @elseif($app->status === 'for_approval') bg-indigo-100 text-indigo-700
                                @elseif($app->status === 'approved') bg-emerald-100 text-emerald-700
                                @elseif($app->status === 'released') bg-teal-100 text-teal-700
                                @elseif($app->status === 'cancelled') bg-slate-200 text-slate-700
                                @else bg-slate-100 text-slate-600
                                @endif">
                                {{ str_replace('_', ' ', $app->status) }}
                            </span>
                        </td>
                        <td class="py-4 text-slate-500">{{ $app->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                            No applications yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

</main>

@endsection
