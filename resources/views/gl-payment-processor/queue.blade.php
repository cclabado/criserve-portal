@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Payment Processor</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">Guarantee Letter Queue</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Review all guarantee letter cases in one place and filter them by the current payment flow.
                </p>
            </div>

            <a href="{{ route('gl-payment-processor.dashboard') }}"
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
            <p class="font-semibold">Please review the submitted details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('gl-payment-processor.queue') }}" class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Reference, client, provider">
            </div>
            <div>
                <label class="label">Payment Status</label>
                <select name="payment_status" class="input">
                    <option value="all" @selected($filters['payment_status'] === 'all')>All payment statuses</option>
                    @foreach($paymentStatusOptions as $status)
                        <option value="{{ $status }}" @selected($filters['payment_status'] === $status)>{{ match ($status) {
                            'awaiting_soa' => 'Awaiting SOA',
                            'for_processing' => 'For Processing',
                            'paid' => 'Paid',
                            default => ucwords(str_replace('_', ' ', $status)),
                        } }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route('gl-payment-processor.queue') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4">
            @forelse($applications as $application)
                @php
                    $latestStatement = $application->documents->where('document_type', 'Updated Statement of Account')->sortByDesc('created_at')->first();
                    $paymentStatusLabel = match ($application->gl_payment_status) {
                        'paid' => 'Paid',
                        'for_processing_cash' => 'For Processing (Cash)',
                        'for_processing_accounting_certification' => 'For Processing (Accounting Certification)',
                        'for_processing_program_amount_approval' => 'For Processing (Program Amount Approval)',
                        'for_processing_accounting' => 'For Processing (Accounting)',
                        'for_processing_budget' => 'For Processing (Budget)',
                        'for_processing_program_approval' => 'For Processing (Program Approval)',
                        default => $latestStatement ? 'For Processing' : 'Awaiting SOA',
                    };
                    $paymentBadgeClass = match ($paymentStatusLabel) {
                        'Paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        'For Processing (Cash)' => 'border-blue-200 bg-blue-50 text-blue-700',
                        'For Processing (Accounting Certification)' => 'border-blue-200 bg-blue-50 text-blue-700',
                        'For Processing (Program Amount Approval)' => 'border-sky-200 bg-sky-50 text-sky-700',
                        'For Processing (Accounting)' => 'border-amber-200 bg-amber-50 text-amber-700',
                        'For Processing (Budget)' => 'border-violet-200 bg-violet-50 text-violet-700',
                        'For Processing (Program Approval)' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
                        'For Processing' => 'border-blue-200 bg-blue-50 text-blue-700',
                        default => 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $application->reference_no }}</p>
                                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->serviceProvider?->name ?? 'No service provider assigned' }}</p>
                            </div>

                            <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <span class="font-semibold text-slate-500">Assistance Status</span><br>
                                    <span class="mt-2 inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">
                                        {{ strtoupper(str_replace('_', ' ', $application->status)) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-semibold text-slate-500">Payment</span><br>
                                    <span class="mt-2 inline-flex rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $paymentBadgeClass }}">
                                        {{ $paymentStatusLabel }}
                                    </span>
                                </div>
                                <div><span class="font-semibold text-slate-500">Final Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</div>
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
