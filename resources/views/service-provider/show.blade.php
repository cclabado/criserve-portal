@extends('layouts.app')

@section('content')

@php
    $hasBeneficiary = (bool) $application->beneficiary;
    $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount)
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
    $soaStatusLabel = ucwords(str_replace('_', ' ', $application->gl_soa_status ?? 'awaiting_upload'));
    $paymentStatusLabel = ucwords(str_replace('_', ' ', $application->gl_payment_status ?? 'unpaid'));
@endphp

<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('service-provider.dashboard') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                    &larr; Back to Guarantee Letters
                </a>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Service Provider Case View</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $application->reference_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Review the approved case information, recommendation, and guarantee letter for {{ $provider->name }}.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                    View Guarantee Letter
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Status</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Client</p>
            <p class="mt-3 text-lg font-black text-slate-900">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Final Amount</p>
            <p class="mt-3 text-2xl font-black text-slate-900">PHP {{ number_format((float) ($application->final_amount ?? $totalRecommendedAmount), 2) }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Statement</p>
            <p class="mt-3 text-lg font-black {{ $hasUpdatedStatement ? 'text-emerald-700' : 'text-amber-700' }}">
                {{ $hasUpdatedStatement ? 'Uploaded' : 'Pending' }}
            </p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">SOA Review</p>
            <p class="mt-3 text-lg font-black text-slate-900">{{ $soaStatusLabel }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment</p>
            <p class="mt-3 text-lg font-black text-slate-900">{{ $paymentStatusLabel }}</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Case Information</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Client Information</h2>
            </div>
        </div>

        <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
            <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->client?->last_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->client?->first_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->client?->middle_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Extension</span><br>{{ $application->client?->extension_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Sex</span><br>{{ $application->client?->sex ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Birthdate</span><br>{{ $application->client?->birthdate ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Civil Status</span><br>{{ $application->client?->civil_status ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->client?->contact_number ?? '-' }}</div>
        </div>

        <div class="mt-4 text-sm">
            <span class="font-semibold text-slate-500">Address</span><br>
            {{ $application->client?->full_address ?? '-' }}
        </div>
    </section>

    @if($hasBeneficiary)
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Case Information</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Beneficiary Information</h2>
            </div>

            <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->beneficiary?->last_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->beneficiary?->first_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->beneficiary?->middle_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Extension</span><br>{{ $application->beneficiary?->extension_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Sex</span><br>{{ $application->beneficiary?->sex ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Birthdate</span><br>{{ $application->beneficiary?->birthdate ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->beneficiary?->contact_number ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Relationship to Client</span><br>{{ $application->beneficiary?->relationshipData?->name ?? '-' }}</div>
            </div>

            <div class="mt-4 text-sm">
                <span class="font-semibold text-slate-500">Address</span><br>
                {{ $application->beneficiary?->full_address ?? '-' }}
            </div>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Assessment</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Initial Assessment</h2>
        </div>

        <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
            <div><span class="font-semibold text-slate-500">Assistance Type</span><br>{{ $application->assistanceType?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Assistance Subtype</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Assistance Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Mode of Assistance</span><br>{{ $application->modeOfAssistance?->name ?? $application->mode_of_assistance ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Amount Needed</span><br>PHP {{ number_format((float) ($application->amount_needed ?? 0), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Recommended Amount</span><br>PHP {{ number_format((float) ($application->recommended_amount ?? 0), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Final Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $totalRecommendedAmount), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Schedule Date</span><br>{{ $application->schedule_date ? $application->schedule_date->format('M d, Y h:i A') : '-' }}</div>
        </div>

        <div class="mt-4 grid gap-4 text-sm md:grid-cols-2">
            <div>
                <span class="font-semibold text-slate-500">Assessment Notes</span><br>
                {{ $application->notes ?: '-' }}
            </div>
            <div>
                <span class="font-semibold text-slate-500">Meeting Link</span><br>
                {{ $application->meeting_link ?: '-' }}
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Recommendation</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Recommendation Summary</h2>
        </div>

        <div class="mt-6 space-y-4 text-sm">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-semibold text-slate-800">Problem Statement</p>
                <p class="mt-2 text-slate-600">{{ $application->problem_statement ?: '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-semibold text-slate-800">Social Worker Assessment</p>
                <p class="mt-2 text-slate-600">{{ $application->social_worker_assessment ?: '-' }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Assistance Recommendation</p>
                    <p class="mt-1 text-sm text-slate-500">Read-only view of the approved recommendation for this guarantee letter case.</p>
                </div>
                <div class="rounded-xl bg-blue-100 px-4 py-3 text-sm font-semibold text-blue-900">
                    Total Final Amount: PHP {{ number_format((float) $totalRecommendedAmount, 2) }}
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($application->assistanceRecommendations as $recommendation)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4">
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
                                <p class="mt-1 text-sm text-slate-500">
                                    Mode: {{ $recommendation->modeOfAssistance?->name ?? '-' }}
                                </p>
                                @if($recommendation->referralInstitution)
                                    <p class="mt-1 text-sm text-slate-500">
                                        Referral: {{ $recommendation->referralInstitution->name }}
                                    </p>
                                @endif
                                @if($recommendation->notes)
                                    <p class="mt-3 text-sm text-slate-600">{{ $recommendation->notes }}</p>
                                @endif
                            </div>

                            <div class="text-left md:text-right">
                                <p class="text-xs text-slate-500">Approved Amount</p>
                                <p class="text-lg font-black text-sky-950">PHP {{ number_format((float) $recommendation->final_amount, 2) }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-6 text-sm text-slate-500">
                        No recommendation items were recorded for this case.
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Statement Upload</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Updated Statement of Account</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Upload the latest statement for this approved guarantee letter once it is available.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-semibold text-slate-800">Current status</p>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $hasUpdatedStatement ? 'Updated statement of account already uploaded for this case.' : 'No updated statement of account has been uploaded yet.' }}
                </p>
                @if($application->gl_soa_review_notes)
                    <p class="mt-3 text-sm text-rose-700">
                        <span class="font-semibold">Return notes:</span> {{ $application->gl_soa_review_notes }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <form method="POST"
                      action="{{ route('service-provider.statement.upload', $application->id) }}"
                      enctype="multipart/form-data"
                      class="space-y-3">
                    @csrf
                    <input type="file" name="statement_file" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                        Upload Statement
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

@endsection
