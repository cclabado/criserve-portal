@extends('layouts.app')

@section('content')

@php
    $latestStatement = $application->documents->where('document_type', 'Updated Statement of Account')->sortByDesc('created_at')->first();
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount)
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
@endphp

<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('gl-payment-processor.dashboard') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                    &larr; Back to GL Processing Queue
                </a>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Payment Processor</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $application->reference_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Review the updated SOA, return the case for compliance when needed, or tag it as processed once complete.
                </p>
            </div>

            <a href="{{ route('gl-payment-processor.guarantee-letter', $application->id) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                View Guarantee Letter
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

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Application</p><p class="mt-3 text-lg font-black text-slate-900">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">SOA Status</p><p class="mt-3 text-lg font-black text-slate-900">{{ ucwords(str_replace('_', ' ', $application->gl_soa_status ?? 'awaiting_upload')) }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment</p><p class="mt-3 text-lg font-black text-slate-900">{{ ucwords(str_replace('_', ' ', $application->gl_payment_status ?? 'unpaid')) }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Provider</p><p class="mt-3 text-lg font-black text-slate-900">{{ $application->serviceProvider?->name ?? '-' }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Final Amount</p><p class="mt-3 text-lg font-black text-slate-900">PHP {{ number_format((float) ($application->final_amount ?? $totalRecommendedAmount), 2) }}</p></article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Client Information</h2>
                <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->client?->last_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->client?->first_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->client?->middle_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->client?->contact_number ?? '-' }}</div>
                </div>
                <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Address</span><br>{{ $application->client?->full_address ?? '-' }}</div>
            </section>

            @if($application->beneficiary)
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-sky-950">Beneficiary Information</h2>
                    <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->beneficiary?->last_name ?? '-' }}</div>
                        <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->beneficiary?->first_name ?? '-' }}</div>
                        <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->beneficiary?->middle_name ?? '-' }}</div>
                        <div><span class="font-semibold text-slate-500">Relationship</span><br>{{ $application->beneficiary?->relationshipData?->name ?? '-' }}</div>
                    </div>
                    <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Address</span><br>{{ $application->beneficiary?->full_address ?? '-' }}</div>
                </section>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Initial Assessment</h2>
                <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="font-semibold text-slate-500">Assistance Type</span><br>{{ $application->assistanceType?->name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Subtype</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Mode</span><br>{{ $application->modeOfAssistance?->name ?? '-' }}</div>
                </div>
                <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Assessment Notes</span><br>{{ $application->notes ?: '-' }}</div>
                <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Social Worker Assessment</span><br>{{ $application->social_worker_assessment ?: '-' }}</div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Recommendation</h2>
                <div class="mt-6 space-y-3">
                    @forelse($application->assistanceRecommendations as $recommendation)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="font-semibold text-sky-900">
                                        {{ $recommendation->assistanceType?->name ?? '-' }}
                                        @if($recommendation->assistanceSubtype)
                                            / {{ $recommendation->assistanceSubtype->name }}
                                        @endif
                                        @if($recommendation->assistanceDetail)
                                            / {{ $recommendation->assistanceDetail->name }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">Mode: {{ $recommendation->modeOfAssistance?->name ?? '-' }}</p>
                                    @if($recommendation->notes)
                                        <p class="mt-2 text-sm text-slate-600">{{ $recommendation->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-left md:text-right">
                                    <p class="text-xs text-slate-500">Final Amount</p>
                                    <p class="text-lg font-black text-slate-900">PHP {{ number_format((float) $recommendation->final_amount, 2) }}</p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No recommendation items were recorded for this case.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Updated SOA Review</h2>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="font-semibold text-slate-800">Latest uploaded statement</p>
                    @if($latestStatement)
                        <p class="mt-2 text-slate-600">{{ $latestStatement->file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ $latestStatement->created_at?->diffForHumans() }}</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('documents.show', $latestStatement->id) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                Review Attachment
                            </a>
                            <a href="{{ route('documents.download', $latestStatement->id) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                Download
                            </a>
                        </div>
                    @else
                        <p class="mt-2 text-slate-600">No updated statement of account has been uploaded yet.</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('gl-payment-processor.soa-review.update', $application->id) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="label">SOA Review Status</label>
                        <select name="gl_soa_status" class="input">
                            <option value="pending_review" @selected(($application->gl_soa_status ?? '') === 'pending_review')>Pending Review</option>
                            <option value="returned_for_compliance" @selected(($application->gl_soa_status ?? '') === 'returned_for_compliance')>Returned for Compliance</option>
                            <option value="processed" @selected(($application->gl_soa_status ?? '') === 'processed')>Processed</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Review Notes</label>
                        <textarea name="gl_soa_review_notes" class="input h-32" placeholder="State missing documents, incorrect files, or any processor notes.">{{ old('gl_soa_review_notes', $application->gl_soa_review_notes) }}</textarea>
                        <p class="mt-2 text-xs text-slate-500">Required when returning the case for compliance.</p>
                    </div>
                    <button type="submit" class="btn-primary">Save SOA Review</button>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Payment Status</h2>
                <form method="POST" action="{{ route('gl-payment-processor.payment-status.update', $application->id) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="label">Guarantee Letter Payment</label>
                        <select name="gl_payment_status" class="input">
                            <option value="unpaid" @selected(($application->gl_payment_status ?? '') === 'unpaid')>Unpaid</option>
                            <option value="paid" @selected(($application->gl_payment_status ?? '') === 'paid')>Paid</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Update Payment Status</button>
                </form>
            </section>

            @if($application->glSoaReviewer || $application->gl_soa_reviewed_at)
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-sky-950">Last Review</h2>
                    <div class="mt-4 text-sm text-slate-600 space-y-2">
                        <p><span class="font-semibold text-slate-800">Reviewed By:</span> {{ $application->glSoaReviewer?->name ?? 'Unknown reviewer' }}</p>
                        <p><span class="font-semibold text-slate-800">Reviewed At:</span> {{ $application->gl_soa_reviewed_at?->format('M d, Y h:i A') ?? '-' }}</p>
                    </div>
                </section>
            @endif
        </div>
    </section>
</main>

@endsection
