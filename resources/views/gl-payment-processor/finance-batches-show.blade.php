@extends('layouts.app')

@section('content')
<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Finance Batch</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $batch->batch_no }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Draft finance packet for grouped guarantee letter transactions. Approvers will still inspect these records one by one even when approval becomes batch-based.
                </p>
            </div>

            <a href="{{ route('gl-payment-processor.finance-batches.ready') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Back to Ready List
            </a>
        </div>
        @if($batch->ors_number || $batch->dv_number || $batch->lddap_ada_number)
            <div class="mt-4 flex flex-wrap gap-3">
                @if($batch->ors_number)
                    <a href="{{ route('gl-finance-batches.documents.ors', $batch->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        View ORS
                    </a>
                @endif
                @if($batch->dv_number)
                    <a href="{{ route('gl-finance-batches.documents.dv', $batch->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                        View DV
                    </a>
                @endif
                @if($batch->lddap_ada_number)
                    <a href="{{ route('gl-finance-batches.documents.lddap-ada', $batch->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        View LDDAP-ADA
                    </a>
                @endif
            </div>
        @endif
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-sky-950">Included GL Records</h2>
                    <p class="mt-1 text-sm text-slate-500">This draft batch keeps individual record access intact.</p>
                </div>
                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                    {{ $batch->application_count }} record{{ $batch->application_count === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Assistance</th>
                            <th class="px-4 py-3">Utilized Amount</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($batch->applications as $application)
                            <tr>
                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $application->pivot->sequence_no }}</td>
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-900">{{ $application->reference_no }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    {{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    {{ $application->assistanceType?->name ?? 'GL Case' }}
                                    @if($application->assistanceDetail?->name)
                                        <p class="mt-1 text-xs text-slate-500">{{ $application->assistanceDetail->name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top font-semibold text-slate-900">
                                    PHP {{ number_format((float) $application->pivot->utilized_amount, 2) }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <a href="{{ route('gl-payment-processor.show', $application->id) }}"
                                       class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">
                                        View Record
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-sky-950">Batch Summary</h2>

            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="font-semibold text-slate-500">Status</dt>
                    <dd class="mt-1 text-slate-900">{{ ucwords(str_replace('_', ' ', $batch->status)) }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Current Stage</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->current_stage ? ucwords(str_replace('_', ' ', $batch->current_stage)) : 'Not assigned yet' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Service Provider</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->serviceProvider?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Fund Source</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->finance_fund_source_name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Bank Account</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->bankAccount?->displayLabel() ?? 'No linked bank account' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Shared ORS</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->ors_number ?: 'Not assigned yet' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Shared DV</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->dv_number ?: 'Not assigned yet' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Shared LDDAP-ADA</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->lddap_ada_number ?: 'Not assigned yet' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Included Amount</dt>
                    <dd class="mt-1 text-lg font-black text-sky-950">PHP {{ number_format((float) $batch->total_amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Created By</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->createdBy?->name ?? $batch->createdBy?->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Created On</dt>
                    <dd class="mt-1 text-slate-900">{{ optional($batch->created_at)->format('M d, Y h:i A') ?? '-' }}</dd>
                </div>
            </dl>
        </section>
    </section>
</main>
@endsection
