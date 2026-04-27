@extends('layouts.app')

@section('content')

@php
    $statusPalette = [
        'For Approval' => ['bar' => 'bg-amber-400', 'dot' => 'bg-amber-400'],
        'Approved' => ['bar' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
        'Denied' => ['bar' => 'bg-rose-500', 'dot' => 'bg-rose-500'],
        'Released' => ['bar' => 'bg-sky-500', 'dot' => 'bg-sky-500'],
    ];
@endphp

<main class="mx-auto max-w-7xl space-y-8 p-6 lg:p-8">

<section class="overflow-hidden rounded-[28px] bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(135deg,_#234E70_0%,_#18384f_46%,_#27597c_100%)] px-8 py-9 text-white shadow-[0_24px_60px_rgba(24,56,79,0.18)]">
    <div class="grid gap-8 lg:grid-cols-[1.5fr_.95fr] lg:items-end">
        <div>
            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                Approving Officer Dashboard
            </span>

            <h1 class="mt-5 max-w-3xl text-3xl font-bold leading-tight sm:text-4xl">
                Final review decisions with a clearer view of today’s approvals and the current queue.
            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                Track pending cases, watch approval activity across the week, and jump straight into the queue or your handled decisions.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-[0.18em] text-white/60">Released This Month</p>
                <div class="mt-3 flex items-end justify-between">
                    <p class="text-4xl font-bold">{{ $releasedThisMonth }}</p>
                    <span class="rounded-full bg-sky-400/15 px-3 py-1 text-xs font-semibold text-sky-100">
                        Closed loop
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/15 p-4">
                <p class="text-xs uppercase tracking-[0.18em] text-white/60">Decision Pulse</p>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    {{ $pending }} waiting, {{ $approvedToday }} approved today, and {{ $deniedToday }} denied today.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Pending Approval</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $pending }}</p>
                <p class="mt-2 text-xs text-amber-600">Cases waiting for final decision</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <span class="material-symbols-outlined text-[28px]">hourglass_top</span>
            </div>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Approved Today</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $approvedToday }}</p>
                <p class="mt-2 text-xs text-emerald-600">Positive decisions today</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <span class="material-symbols-outlined text-[28px]">task_alt</span>
            </div>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Denied Today</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $deniedToday }}</p>
                <p class="mt-2 text-xs text-rose-600">Rejections recorded today</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                <span class="material-symbols-outlined text-[28px]">gpp_bad</span>
            </div>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">My Approvals</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $myApprovals }}</p>
                <p class="mt-2 text-xs text-sky-600">Opened or decided by you</p>
            </div>

            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                <span class="material-symbols-outlined text-[28px]">approval_delegation</span>
            </div>
        </div>
    </article>
</section>

<section class="grid gap-6 xl:grid-cols-[1.55fr_1fr]">
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Weekly Decisions</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Approval Activity</h2>
                <p class="mt-1 text-sm text-slate-500">Approved and denied cases over the last 7 days.</p>
            </div>

            <div class="flex flex-wrap gap-3 text-xs font-semibold">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Approved
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-rose-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    Denied
                </span>
            </div>
        </div>

        <div class="mt-8">
            <div class="flex h-72 items-end gap-4">
                @foreach($decisionTrend as $day)
                    @php
                        $approvedHeight = max((($day['approved'] / $decisionPeak) * 100), $day['approved'] > 0 ? 12 : 0);
                        $deniedHeight = max((($day['denied'] / $decisionPeak) * 100), $day['denied'] > 0 ? 12 : 0);
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-3">
                        <div class="flex h-full w-full items-end justify-center gap-2">
                            <div class="relative flex-1 rounded-t-[18px] bg-gradient-to-t from-emerald-600 to-emerald-300"
                                 style="height: {{ $approvedHeight }}%;">
                                @if($day['approved'] > 0)
                                    <span class="absolute -top-7 left-1/2 -translate-x-1/2 text-[11px] font-bold text-slate-500">
                                        {{ $day['approved'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="relative flex-1 rounded-t-[18px] bg-gradient-to-t from-rose-600 to-rose-300"
                                 style="height: {{ $deniedHeight }}%;">
                                @if($day['denied'] > 0)
                                    <span class="absolute -top-7 left-1/2 -translate-x-1/2 text-[11px] font-bold text-slate-500">
                                        {{ $day['denied'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <p class="text-center text-xs font-medium text-slate-500">{{ $day['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Current Mix</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Decision Status</h2>
        <p class="mt-1 text-sm text-slate-500">Where cases currently sit after final review.</p>

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

<section class="grid gap-6 xl:grid-cols-[1.2fr_.95fr]">
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Quick Actions</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Ready for Review</h2>
                <p class="mt-1 text-sm text-slate-500">Jump into the queue or reopen the approvals you already handled.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <a href="{{ route('approving.applications') }}"
               class="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#234E70] hover:bg-white hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-bold text-[#234E70]">View Approvals</p>
                        <p class="mt-2 text-sm text-slate-500">Open the live queue and process pending requests.</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-[#234E70]">fact_check</span>
                </div>
            </a>

            <a href="{{ route('approving.my-approvals') }}"
               class="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#234E70] hover:bg-white hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-bold text-[#234E70]">My Approvals</p>
                        <p class="mt-2 text-sm text-slate-500">Review the cases you already opened or decided.</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-[#234E70]">approval_delegation</span>
                </div>
            </a>
        </div>
    </article>

    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Recent Activity</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Latest Approval Records</h2>
            <p class="mt-1 text-sm text-slate-500">Most recently handled applications across final review.</p>
        </div>

        <div class="mt-6 space-y-3">
            @forelse($recentApprovals as $app)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $app->reference_no }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $app->client?->first_name }} {{ $app->client?->last_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $app->assistanceType?->name ?? 'No assistance type' }}</p>
                        </div>

                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase
                            @if($app->status === 'approved') bg-emerald-100 text-emerald-700
                            @elseif($app->status === 'denied') bg-rose-100 text-rose-700
                            @elseif($app->status === 'released') bg-sky-100 text-sky-700
                            @else bg-amber-100 text-amber-700
                            @endif">
                            {{ str_replace('_', ' ', $app->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                    No approval activity yet.
                </div>
            @endforelse
        </div>
    </article>
</section>

</main>

@endsection
