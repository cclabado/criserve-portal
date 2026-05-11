@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.55),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#eff6ff_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">Service Provider Workspace</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Guarantee Letter Dashboard</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Manage assigned guarantee letters for {{ $provider->name }}, track updated SOA compliance, and monitor payment and processor review progress from one dashboard.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-sky-200 bg-white/90 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Assigned Exposure</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">PHP {{ number_format((float) $totalFinalAmount, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Released Cases</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $releasedCount }}</p>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Please review the uploaded statement details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Assigned Letters</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ $applications->count() }}</p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700">Awaiting Upload</p>
            <p class="mt-3 text-3xl font-black text-amber-900">{{ $pendingStatementCount }}</p>
        </article>
        <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-700">Pending Review</p>
            <p class="mt-3 text-3xl font-black text-blue-900">{{ $pendingReviewCount }}</p>
        </article>
        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-rose-700">Returned</p>
            <p class="mt-3 text-3xl font-black text-rose-900">{{ $returnedCount }}</p>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Processed</p>
            <p class="mt-3 text-3xl font-black text-emerald-900">{{ $processedCount }}</p>
        </article>
        <article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Paid</p>
            <p class="mt-3 text-3xl font-black text-sky-900">{{ $paidCount }}</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Priority Queue</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">Needs Action</h2>
                </div>
                <a href="{{ route('service-provider.letters') }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    View All Letters
                </a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($priorityApplications as $application)
                    @php
                        $statusTone = match ($application->gl_soa_status) {
                            'returned_for_compliance' => 'border-rose-200 bg-rose-50 text-rose-800',
                            'awaiting_upload' => 'border-amber-200 bg-amber-50 text-amber-800',
                            'pending_review' => 'border-blue-200 bg-blue-50 text-blue-800',
                            default => 'border-slate-200 bg-slate-50 text-slate-700',
                        };
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">{{ $application->reference_no }}</p>
                                <h3 class="mt-2 text-lg font-black text-slate-950">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->assistanceSubtype?->name ?? 'Guarantee letter case' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] {{ $statusTone }}">
                                    {{ ucwords(str_replace('_', ' ', $application->gl_soa_status ?? 'awaiting_upload')) }}
                                </span>
                                <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-600">
                                    {{ ucwords(str_replace('_', ' ', $application->gl_payment_status ?? 'unpaid')) }}
                                </span>
                            </div>
                        </div>

                        @if($application->gl_soa_review_notes)
                            <div class="mt-4 rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-rose-700">
                                <span class="font-semibold">Processor notes:</span> {{ $application->gl_soa_review_notes }}
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('service-provider.show', $application->id) }}"
                               class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                Open Case
                            </a>
                            <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                View GL
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No cases are waiting for action right now.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Workflow Guide</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Next Best Actions</h2>

                <div class="mt-5 space-y-3">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-bold text-amber-900">Awaiting upload</p>
                        <p class="mt-1 text-sm text-amber-800">Upload the updated statement of account for newly assigned or returned guarantee letters.</p>
                    </div>
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-bold text-blue-900">Pending processor review</p>
                        <p class="mt-1 text-sm text-blue-800">Wait for GL processor review after the SOA is uploaded, then watch for any return notes.</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm font-bold text-emerald-900">Processed or paid</p>
                        <p class="mt-1 text-sm text-emerald-800">Keep completed records accessible and use the case view for payment and compliance history.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Recently Cleared</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Processed Cases</h2>

                <div class="mt-5 space-y-3">
                    @forelse($recentlyProcessed as $application)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-sm font-bold text-slate-900">{{ $application->reference_no }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Processed</span>
                                <a href="{{ route('service-provider.show', $application->id) }}" class="text-sm font-semibold text-[#234E70] hover:text-[#18384f]">
                                    View
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No processed guarantee letter cases yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    </section>
</main>

@endsection
