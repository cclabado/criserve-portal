@extends('layouts.app')

@section('content')

@php
    $isApprover = ($workspace ?? 'officer') === 'approver';
    $roleLabel = $isApprover ? 'Budget Approver' : 'Budget Officer';
    $listRoute = $isApprover ? 'budget-approver.gl-payment-approvals' : 'budget-officer.gl-payment-approvals';
    $listLabel = $isApprover ? 'Open For Approval List' : 'Open For Review List';
    $queueLabel = $isApprover ? 'For Approval' : 'For Review';
@endphp

<main class="space-y-6">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(221,214,254,.55),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f5f3ff_48%,_#f8fafc_100%)] p-6 shadow-sm lg:p-8">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_340px] xl:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-violet-700">{{ $roleLabel }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950">Budget Processing Dashboard</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    {{ $isApprover
                        ? 'Track budget-reviewed guarantee letter cases, approve them for accounting, and monitor the final budget-stage workload.'
                        : 'Track guarantee letter cases that already passed program approval, review endorsed fund sources, and manage the budget-stage queue from one workspace.' }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route($listRoute) }}"
                       class="inline-flex items-center rounded-xl bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-800">
                        {{ $listLabel }}
                    </a>
                </div>
            </div>

            <div class="grid gap-3">
                <div class="rounded-3xl border border-slate-200 bg-white/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Queue Total</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['for_review'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">Cases currently waiting for budget handling.</p>
                </div>
                <div class="rounded-3xl border border-violet-200 bg-violet-50/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-violet-700">Total Amount</p>
                    <p class="mt-3 text-3xl font-black text-violet-950">PHP {{ number_format($stats['total_amount'], 2) }}</p>
                    <p class="mt-2 text-sm text-violet-800">Combined endorsed amount in the budget queue.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-violet-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-violet-700">{{ $queueLabel }}</span>
            <p class="mt-3 text-3xl font-black text-violet-950">{{ $stats['for_review'] }}</p>
            <p class="mt-2 text-sm text-violet-800">{{ $isApprover ? 'Budget-reviewed GL cases now waiting for approval.' : 'Approved GL cases now routed to budget processing.' }}</p>
        </article>
        <article class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-sky-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-sky-700">With Remarks</span>
            <p class="mt-3 text-3xl font-black text-sky-950">{{ $stats['with_remarks'] }}</p>
            <p class="mt-2 text-sm text-sky-800">Cases endorsed with notes from the GL payment processor.</p>
        </article>
        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Supporting Docs</span>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $stats['with_supporting_docs'] }}</p>
            <p class="mt-2 text-sm text-emerald-800">Cases with additional supporting files attached by the provider.</p>
        </article>
        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-amber-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">Fund Sources</span>
            <p class="mt-3 text-3xl font-black text-amber-950">{{ $fundSourceBreakdown->count() }}</p>
            <p class="mt-2 text-sm text-amber-800">Distinct finance fund sources active in the queue.</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Latest Endorsements</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $isApprover ? 'Recently Reviewed for Budget Approval' : 'Recently Approved for Budget' }}</h2>
                </div>
                <a href="{{ route($listRoute) }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Open List
                </a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($recentEndorsements as $application)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border border-violet-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-violet-700">
                                        For Processing (Budget)
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $application->reference_no }}</span>
                                </div>
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->serviceProvider?->name ?? 'No provider assigned' }}</p>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                                    <span>Fund Source: {{ $application->gl_finance_fund_source ?? '-' }}</span>
                                    <span>{{ $isApprover ? 'Reviewed' : 'Approved' }} {{ optional($isApprover ? $application->gl_budget_reviewed_at : $application->gl_program_approved_at)->format('M d, Y h:i A') ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="text-left lg:text-right">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Amount</p>
                                <p class="mt-1 text-xl font-black text-slate-950">PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</p>
                                <a href="{{ route($listRoute.'.show', $application->id) }}"
                                   class="mt-4 inline-flex items-center rounded-xl bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800">
                                    {{ $isApprover ? 'Approve Case' : 'Review Case' }}
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No budget endorsements are waiting right now.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Fund Source Load</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Queue by Fund Source</h2>

                <div class="mt-5 space-y-3">
                    @forelse($fundSourceBreakdown as $source)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $source['fund_source'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $source['total'] }} case{{ $source['total'] === 1 ? '' : 's' }}</p>
                                </div>
                                <span class="rounded-full bg-violet-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-violet-700">
                                    PHP {{ number_format($source['amount'], 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No fund source data available yet.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Provider Load</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Top Service Providers</h2>

                <div class="mt-5 space-y-3">
                    @forelse($providerLoad as $provider)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $provider['provider'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $provider['total'] }} case{{ $provider['total'] === 1 ? '' : 's' }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700">
                                    PHP {{ number_format($provider['amount'], 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No provider load data available yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    </section>
</main>

@endsection
