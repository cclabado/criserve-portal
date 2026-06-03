@extends('layouts.app')

@section('content')
<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('accounting-approver.cash-certifications') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                    &larr; Back to Accounting Certification Batches
                </a>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Accounting Certification Batch Workspace</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $batch->batch_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Inspect the included GL records one by one, then certify or return the whole batch.
                </p>
            </div>
            @if($batch->ors_number || $batch->dv_number || $batch->lddap_ada_number)
                <div class="flex flex-wrap gap-3">
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

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment Status</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">
                    {{ $batch->status === 'for_compliance_cash_officer' ? 'For Compliance (Cash Officer)' : ($batch->status === 'for_processing_finance_director' ? 'For Processing (Finance Director)' : ($batch->status === 'disapproved' ? 'Disapproved' : 'For Processing (Accounting Certification)')) }}
                </span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Included Records</p>
            <p class="mt-3 text-lg font-black text-slate-900">{{ $batch->application_count }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Fund Source</p>
            <p class="mt-3 text-lg font-black text-slate-900">{{ $batch->finance_fund_source_name ?? '-' }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Batch Total</p>
            <p class="mt-3 text-lg font-black text-slate-900">PHP {{ number_format((float) $batch->total_amount, 2) }}</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_380px]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-sky-950">Included GL Records</h2>
                    <p class="mt-1 text-sm text-slate-500">Open each record below if you need to inspect it one by one before deciding on the batch.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Client</th>
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
                                    <p class="mt-1 text-xs text-slate-500">{{ $application->assistanceDetail?->name ?? ($application->assistanceType?->name ?? 'GL Case') }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    {{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}
                                </td>
                                <td class="px-4 py-4 align-top font-semibold text-slate-900">PHP {{ number_format((float) $application->pivot->utilized_amount, 2) }}</td>
                                <td class="px-4 py-4 align-top">
                                    <a href="{{ route('accounting-approver.cash-certifications.records.show', [$batch->id, $application->id]) }}"
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
            <h2 class="text-xl font-black text-sky-950">Batch Decision</h2>

            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="font-semibold text-slate-500">Service Provider</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->serviceProvider?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Bank Account</dt>
                    <dd class="mt-1 text-slate-900">{{ $batch->bankAccount?->displayLabel() ?? '-' }}</dd>
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
            </dl>

            <form method="POST" action="{{ route('accounting-approver.cash-certifications.update', $batch->id) }}" class="mt-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="label">Certification Decision</label>
                    <select name="decision" class="input">
                        <option value="for_compliance" @selected(old('decision') === 'for_compliance')>For Compliance</option>
                        <option value="approved" @selected(old('decision') === 'approved')>Approved</option>
                        <option value="disapproved" @selected(old('decision') === 'disapproved')>Disapproved</option>
                    </select>
                </div>

                <div>
                    <label class="label">Compliance Trigger Record</label>
                    <select name="trigger_application_id" class="input">
                        <option value="">Select record when returning for compliance</option>
                        @foreach($batch->applications as $application)
                            <option value="{{ $application->id }}" @selected((string) old('trigger_application_id') === (string) $application->id)>
                                {{ $application->reference_no }} - {{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: 'Client' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Remarks / Reason</label>
                    <textarea name="remarks" class="input h-32" placeholder="Add the reason for compliance return or disapproval.">{{ old('remarks', $batch->accounting_certification_remarks) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Remarks are required when the decision is For Compliance or Disapproved.</p>
                </div>

                <button type="submit" class="btn-primary">Save Decision</button>
            </form>
        </section>
    </section>
</main>

@endsection
