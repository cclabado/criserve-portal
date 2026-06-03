@extends('layouts.app')

@section('content')
@php
    $isFinishedScope = ($filters['scope'] ?? 'active') === 'finished';
@endphp

<main class="p-8 max-w-7xl mx-auto space-y-6">
    <section class="rounded-[28px] bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(135deg,_#234E70_0%,_#18384f_46%,_#27597c_100%)] px-8 py-9 text-white shadow-[0_24px_60px_rgba(24,56,79,0.18)]">
        <div class="grid gap-6 lg:grid-cols-[1.4fr_.9fr] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                    Approving Officer
                </span>
                <h1 class="mt-5 text-3xl font-bold leading-tight sm:text-4xl">Program Amount Approval Batches</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                    Approve grouped GL finance batches after accounting approval, while still reviewing each included record one by one if needed.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-white/60">Queue Total</p>
                    <p class="mt-3 text-4xl font-bold">{{ $queueStats['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-950/15 p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-white/60">With Remarks</p>
                    <p class="mt-3 text-4xl font-bold">{{ $queueStats['with_remarks'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('approving.gl-program-amount-approvals', ['scope' => 'active']) }}"
               class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold {{ $isFinishedScope ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-[#234E70] text-white' }}">
                For Approval
            </a>
            <a href="{{ route('approving.gl-program-amount-approvals', ['scope' => 'finished']) }}"
               class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold {{ $isFinishedScope ? 'bg-[#234E70] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Completed
            </a>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('approving.gl-program-amount-approvals') }}" class="grid gap-4 md:grid-cols-4">
            <input type="hidden" name="scope" value="{{ $filters['scope'] ?? 'active' }}">
            <div>
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Batch, reference, client, provider">
            </div>
            <div>
                <label class="label">Fund Source</label>
                <select name="fund_source" class="input">
                    <option value="all" @selected($filters['fund_source'] === 'all')>All fund sources</option>
                    @foreach($fundSources as $fundSource)
                        <option value="{{ $fundSource }}" @selected($filters['fund_source'] === $fundSource)>{{ $fundSource }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Payment Status</label>
                <select name="payment_status" class="input">
                    <option value="all" @selected($filters['payment_status'] === 'all')>All payment statuses</option>
                    @foreach($paymentStatusOptions as $paymentStatus)
                        <option value="{{ $paymentStatus }}" @selected($filters['payment_status'] === $paymentStatus)>{{ match ($paymentStatus) {
                            'for_processing_program_amount_approval' => 'For Processing (Program Amount Approval)',
                            'for_compliance_accounting_officer' => 'For Compliance (Accounting Officer)',
                            'for_processing_cash' => 'For Processing (Cash)',
                            'disapproved' => 'Disapproved',
                            default => ucwords(str_replace('_', ' ', $paymentStatus)),
                        } }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route('approving.gl-program-amount-approvals') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Batch</th>
                        <th class="px-5 py-4">Provider</th>
                        <th class="px-5 py-4">Fund Source</th>
                        <th class="px-5 py-4">Bank Account</th>
                        <th class="px-5 py-4">Records</th>
                        <th class="px-5 py-4">Amount</th>
                        <th class="px-5 py-4">Payment Status</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($batches as $batch)
                        @php
                            $isHistoricalRow = (int) ($batch->program_amount_approved_by ?? 0) === (int) auth()->id()
                                && ! in_array($batch->program_amount_approval_status, ['pending_approval', null], true);
                            $rowStatusLabel = match ($batch->status) {
                                'for_compliance_accounting_officer' => 'For Compliance (Accounting Officer)',
                                'for_processing_cash' => 'For Processing (Cash)',
                                'disapproved' => 'Disapproved',
                                default => 'For Processing (Program Amount Approval)',
                            };
                            $rowStatusClass = match ($rowStatusLabel) {
                                'For Compliance (Accounting Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
                                'For Processing (Cash)' => 'border-amber-200 bg-amber-50 text-amber-700',
                                'Disapproved' => 'border-slate-300 bg-slate-100 text-slate-700',
                                default => 'border-sky-200 bg-sky-50 text-sky-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4 align-middle">
                                <p class="font-semibold text-slate-900">{{ $batch->batch_no }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $isHistoricalRow ? 'Handled' : 'Ready' }}
                                    {{ $batch->updated_at?->format('M d, Y h:i A') ?? '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $batch->serviceProvider?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $batch->finance_fund_source_name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $batch->bankAccount?->displayLabel() ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $batch->application_count }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">PHP {{ number_format((float) $batch->total_amount, 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex min-h-9 items-center justify-center rounded-full border px-3 py-1 text-center text-[11px] font-bold uppercase leading-tight tracking-[0.16em] {{ $rowStatusClass }}">
                                    {{ $rowStatusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('approving.gl-program-amount-approvals.show', $batch->id) }}"
                                   class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    {{ $isFinishedScope || $isHistoricalRow ? 'View Batch' : 'Approve Batch' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                No program amount approval batches are waiting right now.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $batches->links() }}
        </div>
    </section>
</main>

@endsection
