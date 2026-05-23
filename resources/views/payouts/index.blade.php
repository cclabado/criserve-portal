@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(220,252,231,.75),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-700">
                    {{ $canCreateBatches ? ($isReportingOfficer ? 'Reporting Officer' : 'Administrator') : 'Staff Access' }}
                </p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950">Offsite Payout Module</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Upload the clean deduplication list, assign the payout venue and target sector, and manage beneficiaries one by one during field payout operations.
                </p>
            </div>

            <a href="{{ $dashboardRoute }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Back to Dashboard
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Please review the payout batch details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Payout Batches</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['total_batches'] }}</p>
        </article>
        <article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Today</p>
            <p class="mt-3 text-3xl font-black text-sky-950">{{ $stats['today_batches'] }}</p>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Scheduled Ahead</p>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $stats['scheduled_batches'] }}</p>
        </article>
    </section>

    @if($canCreateBatches)
    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(360px,.95fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Create Batch</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Upload Clean Payout List</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Use the clean file from bulk deduplication, then tag it with the payout campaign details such as venue, schedule, and sector grouping.
                </p>
            </div>

            <form method="POST" action="{{ route($storeRoute) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Batch Name</label>
                        <input type="text" name="batch_name" class="input" value="{{ old('batch_name') }}" placeholder="Typhoon Odette Payout - Barangay Hall" required>
                    </div>
                    <div>
                        <label class="label">Primary Sector</label>
                        <input type="text" name="sector_label" class="input" value="{{ old('sector_label') }}" placeholder="Drivers, fire victims, typhoon affected" required>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Venue</label>
                        <input type="text" name="venue" class="input" value="{{ old('venue') }}" placeholder="City covered court / barangay hall" required>
                    </div>
                    <div>
                        <label class="label">Fixed Payout Amount</label>
                        <input type="number" name="payout_amount" class="input" value="{{ old('payout_amount') }}" min="0.01" step="0.01" placeholder="1000.00" required>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Payout Date</label>
                        <input type="date" name="payout_date" class="input" value="{{ old('payout_date') }}">
                    </div>
                    <div>
                        <label class="label">Source Deduplication Run</label>
                        <select name="bulk_deduplication_run_id" class="input">
                            <option value="">Uploaded clean file only</option>
                            @foreach($completedRuns as $run)
                                <option value="{{ $run->id }}" @selected((string) old('bulk_deduplication_run_id') === (string) $run->id)>
                                    Run #{{ $run->id }} · {{ $run->original_filename }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Clean List File</label>
                        <input type="file" name="spreadsheet" class="input" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>

                <div>
                    <label class="label">Operational Notes</label>
                    <textarea name="notes" class="input" placeholder="Optional instructions for payout marshals, required IDs, sector ordering, or venue reminders.">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-[#1f7a4d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#17613d]">
                        Create Payout Batch
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Workflow</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">How This Fits</h2>

            <div class="mt-5 space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-900">1. Deduplicate in bulk</p>
                    <p class="mt-1 text-sm text-slate-600">Generate the clean list from the bulk deduplication workspace to remove exact duplicates and questionable matches first.</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                    <p class="text-sm font-bold text-sky-900">2. Upload to payout batch</p>
                    <p class="mt-1 text-sm text-slate-700">Create one batch per venue or sector campaign, such as drivers, typhoon-affected families, or fire victims.</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-bold text-emerald-900">3. Pay out one by one</p>
                    <p class="mt-1 text-sm text-slate-700">Open the batch at the venue and mark every beneficiary as paid, absent, deferred, or still pending.</p>
                </div>
            </div>
        </section>
    </section>
    @else
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Live Access</p>
        <h2 class="mt-2 text-2xl font-black text-slate-950">Activated Payouts</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            This account can open payout batches only after an administrator activates them for field operations.
        </p>
    </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Existing Batches</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Recent Payout Runs</h2>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse($batches as $batch)
                @php
                    $summary = $batch->summary ?? [];
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">{{ $batch->sector_label }}</p>
                            <h3 class="mt-2 text-xl font-black text-slate-950">{{ $batch->batch_name }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $batch->venue }} · {{ $batch->payout_date?->format('M d, Y') ?? 'Schedule pending' }}</p>
                            <p class="mt-1 text-sm text-slate-500">Source: {{ $batch->source_filename }}</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-700">Fixed payout: PHP {{ number_format((float) $batch->payout_amount, 2) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $batch->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $batch->is_active ? 'Active for staff' : 'Inactive draft' }}
                                </span>
                                @if($batch->is_active && $batch->activated_at)
                                    <span class="text-xs text-slate-500">
                                        Activated {{ $batch->activated_at->format('M d, Y h:i A') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-900">Total:</span> {{ $summary['total_entries'] ?? $batch->entries_count }}
                            </div>
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                <span class="font-semibold">Paid:</span> {{ $summary['paid_count'] ?? 0 }}
                            </div>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <span class="font-semibold">Pending:</span> {{ $summary['pending_count'] ?? 0 }}
                            </div>
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                                <span class="font-semibold">Absent/Deferred:</span> {{ ($summary['absent_count'] ?? 0) + ($summary['deferred_count'] ?? 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route($showRoute, $batch) }}"
                           class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                            Open Batch
                        </a>

                        @if($canGenerateReport)
                            <a href="{{ route($reportRouteName, $batch) }}"
                               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                Generate Report
                            </a>
                        @endif

                        @if($canToggleActivation)
                            <form method="POST" action="{{ route($activationRouteName, $batch) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $batch->is_active ? 0 : 1 }}">
                                <button type="submit"
                                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold {{ $batch->is_active ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                                    {{ $batch->is_active ? 'Deactivate' : 'Activate for Staff' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    No payout batches yet. Upload the first clean list to start the offsite payout workflow.
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $batches->links() }}
        </div>
    </section>
</main>

@endsection
