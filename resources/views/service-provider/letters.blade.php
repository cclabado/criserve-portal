@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.45),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">Service Provider Workspace</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Assigned Guarantee Letters</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Review every assigned guarantee letter for {{ $provider->name }}, open case details, view the printed GL, and upload updated statements of account.
                </p>
            </div>

            <a href="{{ route('service-provider.dashboard') }}"
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
            <p class="font-semibold">Please review the uploaded statement details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Case Inventory</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">All Assigned Guarantee Letters</h2>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                {{ $applications->count() }} total case{{ $applications->count() === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse($applications as $application)
                @php
                    $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
                    $soaStatusLabel = ucwords(str_replace('_', ' ', $application->gl_soa_status ?? 'awaiting_upload'));
                    $paymentStatusLabel = ucwords(str_replace('_', ' ', $application->gl_payment_status ?? 'unpaid'));
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">{{ $application->reference_no }}</p>
                                <h3 class="mt-2 text-xl font-black text-slate-950">
                                    {{ $application->client?->first_name }} {{ $application->client?->last_name }}
                                </h3>
                            </div>

                            <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-6">
                                <div><span class="font-semibold text-slate-500">Application</span><br>{{ strtoupper(str_replace('_', ' ', $application->status)) }}</div>
                                <div><span class="font-semibold text-slate-500">Assistance</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
                                <div><span class="font-semibold text-slate-500">Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
                                <div><span class="font-semibold text-slate-500">Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</div>
                                <div><span class="font-semibold text-slate-500">SOA Review</span><br>{{ $soaStatusLabel }}</div>
                                <div><span class="font-semibold text-slate-500">Payment</span><br>{{ $paymentStatusLabel }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-800">Current statement status</p>
                                <p class="mt-1">{{ $hasUpdatedStatement ? 'Updated statement of account already uploaded.' : 'Waiting for updated statement of account upload.' }}</p>
                                @if($application->gl_soa_review_notes)
                                    <p class="mt-2 text-rose-700"><span class="font-semibold">Processor notes:</span> {{ $application->gl_soa_review_notes }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('service-provider.show', $application->id) }}"
                                   class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    View Case Details
                                </a>

                                <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                    View Guarantee Letter
                                </a>
                            </div>
                        </div>

                        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Upload Updated Statement</p>
                            <form method="POST"
                                  action="{{ route('service-provider.statement.upload', $application->id) }}"
                                  enctype="multipart/form-data"
                                  class="mt-4 space-y-3">
                                @csrf
                                <input type="file" name="statement_file" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <button type="submit" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    Upload Statement
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    No approved guarantee letters are assigned to this service provider account yet.
                </div>
            @endforelse
        </div>
    </section>
</main>

@endsection
