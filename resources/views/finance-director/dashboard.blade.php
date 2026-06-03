@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.55),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#eef6ff_48%,_#f8fafc_100%)] p-6 shadow-sm lg:p-8">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_340px] xl:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">Finance Director</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950">Finance Director Dashboard</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Final guarantee letter approvals land here after accounting certification. Approving a case here tags the payment status as Paid.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('finance-director.gl-payment-approvals') }}"
                       class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#18384f]">
                        Open Final Approval Batches
                    </a>
                </div>
            </div>

            <div class="grid gap-3">
                <div class="rounded-3xl border border-slate-200 bg-white/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Queue Total</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">Cases waiting for final finance director approval.</p>
                </div>
                <div class="rounded-3xl border border-sky-200 bg-sky-50/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Total Amount</p>
                    <p class="mt-3 text-3xl font-black text-sky-950">PHP {{ number_format($stats['total_amount'], 2) }}</p>
                    <p class="mt-2 text-sm text-sky-800">Combined amount currently awaiting final approval.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-blue-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">Approval Batches</span>
            <p class="mt-3 text-3xl font-black text-blue-950">{{ $stats['total'] }}</p>
            <p class="mt-2 text-sm text-blue-800">Active cases in the final finance director queue.</p>
        </article>
        <article class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-sky-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-sky-700">With Remarks</span>
            <p class="mt-3 text-3xl font-black text-sky-950">{{ $stats['with_remarks'] }}</p>
            <p class="mt-2 text-sm text-sky-800">Cases carrying cash-stage certification remarks.</p>
        </article>
        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Supporting Docs</span>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $stats['with_supporting_docs'] }}</p>
            <p class="mt-2 text-sm text-emerald-800">Cases with additional provider attachments.</p>
        </article>
        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">Stage</span>
            <p class="mt-3 text-2xl font-black text-slate-950">For Processing</p>
            <p class="mt-2 text-sm text-slate-700">All records show `For Processing (Finance Director)` until approved.</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Recent Queue</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Latest Final Approval Batches</h2>
                </div>
                <a href="{{ route('finance-director.gl-payment-approvals') }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Open Batches
                </a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($recentBatches as $batch)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border border-blue-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">
                                        For Processing (Finance Director)
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $batch->batch_no }}</span>
                                </div>
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $batch->serviceProvider?->name ?? 'No provider assigned' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $batch->application_count }} record{{ $batch->application_count === 1 ? '' : 's' }}</p>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                                    <span>Fund Source: {{ $batch->finance_fund_source_name ?? '-' }}</span>
                                    <span>Amount: PHP {{ number_format((float) $batch->total_amount, 2) }}</span>
                                </div>
                            </div>
                            <a href="{{ route('finance-director.gl-payment-approvals.show', $batch->id) }}"
                               class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                Open Batch
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No finance director cases are waiting right now.
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
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-sky-700">
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
</main>

@endsection
