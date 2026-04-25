@extends('layouts.app')

@section('content')

<main class="p-8 space-y-6 max-w-7xl mx-auto">

<header class="rounded-[28px] bg-gradient-to-br from-[#103b5b] via-[#1f5c84] to-[#3b89b8] p-8 text-white shadow-lg">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-xs uppercase tracking-[0.28em] text-white/75">Social Worker Module</p>
            <h1 class="mt-3 text-3xl font-black leading-tight">My Schedule</h1>
            <p class="mt-3 max-w-2xl text-sm text-white/85">
                View every assessment schedule you handled, with the Google Meet link and calendar access ready on each appointment.
            </p>
        </div>

        <div class="rounded-2xl border border-white/20 bg-white/10 px-5 py-4 backdrop-blur">
            <p class="text-xs uppercase tracking-[0.22em] text-white/70">Google Calendar</p>
            @if($googleConnected)
                <p class="mt-2 text-lg font-bold">Connected</p>
                <p class="text-sm text-white/80">{{ auth()->user()->google_email ?: 'Calendar sync is active' }}</p>
            @else
                <p class="mt-2 text-lg font-bold">Not Connected</p>
                <p class="text-sm text-white/80">Connect your Google account in Profile to auto-create Meet links.</p>
            @endif
        </div>
    </div>
</header>

<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl bg-white p-6 shadow">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Total Schedules</p>
        <p class="mt-3 text-3xl font-black text-slate-900">{{ $totalScheduled }}</p>
        <p class="mt-2 text-sm text-slate-500">All scheduled assessments assigned to you.</p>
    </article>

    <article class="rounded-2xl bg-white p-6 shadow">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Today</p>
        <p class="mt-3 text-3xl font-black text-slate-900">{{ $todayCount }}</p>
        <p class="mt-2 text-sm text-slate-500">Appointments happening on {{ now()->format('F d, Y') }}.</p>
    </article>

    <article class="rounded-2xl bg-white p-6 shadow">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Upcoming</p>
        <p class="mt-3 text-3xl font-black text-slate-900">{{ $upcomingCount }}</p>
        <p class="mt-2 text-sm text-slate-500">Future schedules still waiting to happen.</p>
    </article>
</section>

<form method="GET" action="{{ route('socialworker.schedule') }}"
      class="rounded-2xl bg-white p-5 shadow">
    <div class="grid gap-4 xl:grid-cols-[1.2fr,.65fr,.7fr,.7fr,auto] xl:items-end">
        <div>
            <label class="label">Search case, client, or beneficiary</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Reference no., client name, or beneficiary name"
                   class="input">
        </div>

        <div>
            <label class="label">Scope</label>
            <select name="schedule_scope" class="input">
                <option value="">All schedules</option>
                <option value="upcoming" @selected(request('schedule_scope') === 'upcoming')>Upcoming</option>
                <option value="past" @selected(request('schedule_scope') === 'past')>Past</option>
            </select>
        </div>

        <div>
            <label class="label">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input">
        </div>

        <div>
            <label class="label">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input">
        </div>

        <div class="flex gap-2">
            <a href="{{ route('socialworker.schedule') }}" class="btn-secondary text-center">Reset</a>
            <button type="submit" class="btn-primary">Filter</button>
        </div>
    </div>
    @if(! $googleConnected)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Google Calendar is not connected yet. Meeting links will only auto-generate after you connect your Google account from
            <a href="{{ route('profile.edit') }}" class="font-semibold underline underline-offset-2">Profile</a>.
        </div>
    @endif
</form>

<section class="space-y-4">
    @forelse($schedules as $schedule)
        @php
            $clientName = trim(implode(' ', array_filter([
                $schedule->client?->first_name,
                $schedule->client?->middle_name,
                $schedule->client?->last_name,
                $schedule->client?->extension_name,
            ])));

            $beneficiaryName = trim(implode(' ', array_filter([
                $schedule->beneficiary?->first_name,
                $schedule->beneficiary?->middle_name,
                $schedule->beneficiary?->last_name,
                $schedule->beneficiary?->extension_name,
            ])));

            $relationshipName = $schedule->beneficiary?->relationshipData?->name;
            $isPast = optional($schedule->schedule_date)->lt(now());
        @endphp

        <article class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[220px,1fr]">
                <div class="bg-slate-900 px-6 py-6 text-white">
                    <p class="text-xs uppercase tracking-[0.26em] text-white/60">Schedule</p>
                    <p class="mt-3 text-3xl font-black">{{ optional($schedule->schedule_date)->format('d') }}</p>
                    <p class="text-lg font-semibold">{{ optional($schedule->schedule_date)->format('M Y') }}</p>
                    <p class="mt-4 text-sm text-white/80">{{ optional($schedule->schedule_date)->format('l') }}</p>
                    <p class="text-base font-semibold">{{ optional($schedule->schedule_date)->format('h:i A') }}</p>
                    <span class="mt-5 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $isPast ? 'bg-slate-700 text-slate-100' : 'bg-emerald-500/20 text-emerald-100' }}">
                        {{ $isPast ? 'Completed / Past' : 'Upcoming' }}
                    </span>
                </div>

                <div class="p-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Reference</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-900">{{ $schedule->reference_no }}</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Client</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $clientName ?: 'N/A' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Beneficiary</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $beneficiaryName ?: 'Self / Client' }}</p>
                                    @if($beneficiaryName && $relationshipName)
                                        <p class="mt-1 text-sm text-slate-500">Client's relationship to beneficiary: {{ $relationshipName }}</p>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Assistance</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">
                                        {{ $schedule->assistanceType?->name ?: 'Not set' }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $schedule->assistanceSubtype?->name ?: 'Subtype not set' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Status</p>
                                    <p class="mt-2 inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-sky-800">
                                        {{ str_replace('_', ' ', $schedule->status) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="min-w-[260px] rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Meeting Access</p>

                            @if($schedule->meeting_link)
                                <div class="mt-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-sm font-semibold text-slate-900">Google Meet Link</p>
                                    <a href="{{ $schedule->meeting_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="mt-2 block break-all text-sm font-medium text-sky-700 underline underline-offset-2">
                                        {{ $schedule->meeting_link }}
                                    </a>
                                </div>
                            @else
                                <div class="mt-3 rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                    No meeting link saved yet for this schedule.
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($schedule->meeting_link)
                                    <a href="{{ $schedule->meeting_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1d3f5c]">
                                        Open Meet
                                    </a>
                                @endif

                                @if($schedule->google_calendar_event_link)
                                    <a href="{{ $schedule->google_calendar_event_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                        Open Calendar
                                    </a>
                                @endif

                                <a href="{{ route('socialworker.show', $schedule->id) }}"
                                   class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                    View Case
                                </a>
                            </div>
                        </div>
                    </div>

                    @if($schedule->notes)
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Assessment Note</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $schedule->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-14 text-center shadow-sm">
            <p class="text-lg font-bold text-slate-900">No schedules found.</p>
            <p class="mt-2 text-sm text-slate-500">
                Once you save an initial assessment schedule, it will appear here with its meeting link.
            </p>
        </div>
    @endforelse
</section>

<div class="rounded-2xl bg-white px-6 py-4 shadow">
    {{ $schedules->links() }}
</div>

</main>

@endsection
