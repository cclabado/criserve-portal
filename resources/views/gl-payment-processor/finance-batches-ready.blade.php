@extends('layouts.app')

@section('content')
<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Finance Batches</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">Ready For Batch</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Group GL cases that already share the same service provider, finance fund source, and bank account into a draft finance batch.
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
            <p class="font-semibold">Please review the batch details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('gl-payment-processor.finance-batches.ready') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label class="label">Search</label>
                        <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Reference, client, provider">
                    </div>
                    <div>
                        <label class="label">Service Provider</label>
                        <select name="service_provider_id" class="input">
                            <option value="">All providers</option>
                            @foreach($serviceProviders as $providerId => $providerName)
                                <option value="{{ $providerId }}" @selected((string) $filters['service_provider_id'] === (string) $providerId)>{{ $providerName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Fund Source</label>
                        <select name="fund_source" class="input">
                            <option value="">All fund sources</option>
                            @foreach($fundSources as $fundSource)
                                <option value="{{ $fundSource }}" @selected($filters['fund_source'] === $fundSource)>{{ $fundSource }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Bank Account</label>
                        <select name="bank_account_id" class="input">
                            <option value="">All bank accounts</option>
                            @foreach($bankAccountOptions as $bankAccountId => $bankAccountLabel)
                                <option value="{{ $bankAccountId }}" @selected((string) $filters['bank_account_id'] === (string) $bankAccountId)>{{ $bankAccountLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3 md:col-span-2 xl:col-span-5">
                        <button type="submit" class="btn-primary">Apply Filters</button>
                        <a href="{{ route('gl-payment-processor.finance-batches.ready') }}" class="btn-secondary">Reset</a>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('gl-payment-processor.finance-batches.store') }}" class="space-y-5">
                    @csrf

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-sky-950">Eligible GL Cases</h2>
                            <p class="mt-1 text-sm text-slate-500">Select matching cases and create one draft finance batch.</p>
                        </div>
                        <button type="submit" class="btn-primary">Create Batch</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                                    <th class="px-4 py-3"><span class="sr-only">Select</span></th>
                                    <th class="px-4 py-3">Reference</th>
                                    <th class="px-4 py-3">Client</th>
                                    <th class="px-4 py-3">Provider</th>
                                    <th class="px-4 py-3">Fund Source</th>
                                    <th class="px-4 py-3">Bank Account</th>
                                    <th class="px-4 py-3">Utilized Amount</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($applications as $application)
                                    @php
                                        $statementSummary = $application->documents
                                            ->where('document_type', 'Updated Statement of Account')
                                            ->sortByDesc('created_at')
                                            ->first()?->bankAccountSummary();
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="checkbox"
                                                name="application_ids[]"
                                                value="{{ $application->id }}"
                                                class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                                                @checked(collect(old('application_ids', []))->contains($application->id))
                                            >
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <p class="font-semibold text-slate-900">{{ $application->reference_no }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</p>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            {{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            {{ $application->serviceProvider?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            {{ $application->gl_finance_fund_source }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            {{ $statementSummary ?: 'Linked bank account #'.$application->latest_statement_bank_account_id }}
                                        </td>
                                        <td class="px-4 py-4 align-top font-semibold text-slate-900">
                                            PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <a href="{{ route('gl-payment-processor.show', $application->id) }}"
                                               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                                View Record
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
                                            No GL cases are currently eligible for draft batching.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($applications, 'links'))
                        <div>
                            {{ $applications->links() }}
                        </div>
                    @endif
                </form>
            </section>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-sky-950">Draft Batches</h2>
            <p class="mt-1 text-sm text-slate-500">Recently created finance batches.</p>

            <div class="mt-5 space-y-4">
                @forelse($draftBatches as $batch)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $batch->batch_no }}</p>
                        <h3 class="mt-2 text-base font-bold text-slate-900">{{ $batch->serviceProvider?->name ?? '-' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $batch->finance_fund_source_name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $batch->bankAccount?->displayLabel() ?? 'No linked bank account' }}</p>

                        <div class="mt-4 grid gap-3 text-sm text-slate-600">
                            <div><span class="font-semibold text-slate-500">Included GLs:</span> {{ $batch->application_count }}</div>
                            <div><span class="font-semibold text-slate-500">Total:</span> PHP {{ number_format((float) $batch->total_amount, 2) }}</div>
                        </div>

                        <a href="{{ route('gl-payment-processor.finance-batches.show', $batch) }}"
                           class="mt-4 inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                            Open Batch
                        </a>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No draft finance batches yet.
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</main>
@endsection
