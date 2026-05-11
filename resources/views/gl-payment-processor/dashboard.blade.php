@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Payment Processor</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">Guarantee Letter Processing Queue</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Monitor guarantee letter payment status, review updated statements of account, return incomplete submissions for compliance, and tag completed cases as processed.
                </p>
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
            <p class="font-semibold">Please review the submitted details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Total</p><p class="mt-3 text-3xl font-black text-slate-900">{{ $stats['total'] }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Awaiting Upload</p><p class="mt-3 text-3xl font-black text-slate-900">{{ $stats['awaiting_upload'] }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Pending Review</p><p class="mt-3 text-3xl font-black text-amber-700">{{ $stats['pending_review'] }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Returned</p><p class="mt-3 text-3xl font-black text-rose-700">{{ $stats['returned'] }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Processed</p><p class="mt-3 text-3xl font-black text-emerald-700">{{ $stats['processed'] }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Paid</p><p class="mt-3 text-3xl font-black text-sky-700">{{ $stats['paid'] }}</p></article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('gl-payment-processor.dashboard') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Reference, client, provider">
            </div>
            <div>
                <label class="label">Payment Status</label>
                <select name="payment_status" class="input">
                    <option value="all" @selected($filters['payment_status'] === 'all')>All payment statuses</option>
                    @foreach($paymentStatusOptions as $status)
                        <option value="{{ $status }}" @selected($filters['payment_status'] === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">SOA Status</label>
                <select name="soa_status" class="input">
                    <option value="all" @selected($filters['soa_status'] === 'all')>All SOA statuses</option>
                    @foreach($soaStatusOptions as $status)
                        <option value="{{ $status }}" @selected($filters['soa_status'] === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route('gl-payment-processor.dashboard') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4">
            @forelse($applications as $application)
                @php
                    $latestStatement = $application->documents->where('document_type', 'Updated Statement of Account')->sortByDesc('created_at')->first();
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $application->reference_no }}</p>
                                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->serviceProvider?->name ?? 'No service provider assigned' }}</p>
                            </div>

                            <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-5">
                                <div><span class="font-semibold text-slate-500">Application</span><br>{{ strtoupper(str_replace('_', ' ', $application->status)) }}</div>
                                <div><span class="font-semibold text-slate-500">SOA Status</span><br>{{ ucwords(str_replace('_', ' ', $application->gl_soa_status ?? 'awaiting_upload')) }}</div>
                                <div><span class="font-semibold text-slate-500">Payment</span><br>{{ ucwords(str_replace('_', ' ', $application->gl_payment_status ?? 'unpaid')) }}</div>
                                <div><span class="font-semibold text-slate-500">Final Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</div>
                                <div><span class="font-semibold text-slate-500">Statement File</span><br>{{ $latestStatement?->file_name ?? 'No updated SOA yet' }}</div>
                            </div>

                            @if($application->gl_soa_review_notes)
                                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <span class="font-semibold">Review notes:</span> {{ $application->gl_soa_review_notes }}
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('gl-payment-processor.show', $application->id) }}"
                               class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                Review Case
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    No guarantee letter cases matched the current queue filters.
                </div>
            @endforelse
        </div>
    </section>
</main>

@endsection
