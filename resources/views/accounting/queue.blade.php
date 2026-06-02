@extends('layouts.app')

@section('content')

@php
    $isOfficer = $workspace === 'officer';
    $isCashCertifier = $workspace === 'cash_certifier';
    $isFinishedScope = ($filters['scope'] ?? 'active') === 'finished';
    $workspaceLabel = $isOfficer ? 'Accounting Officer' : 'Accounting Approver';
    $indexRoute = $isOfficer
        ? 'accounting-officer.gl-payment-reviews'
        : ($isCashCertifier ? 'accounting-approver.cash-certifications' : 'accounting-approver.gl-payment-approvals');
    $showRoute = $indexRoute.'.show';
    $statusLabel = $isCashCertifier ? 'For Processing (Accounting Certification)' : 'For Processing (Accounting)';
    $queueTitle = $isOfficer
        ? 'Accounting Review Queue'
        : ($isCashCertifier ? 'Cash Certification Queue' : 'Accounting Approval Queue');
    $queueDescription = $isOfficer
        ? 'Review guarantee letter cases already cleared by budget before sending them to accounting approval.'
        : ($isCashCertifier
            ? 'Approve and certify cash-cleared guarantee letter cases after the cash approver endorsement.'
            : 'Approve accounting-reviewed guarantee letter cases while keeping them within the accounting stage.');
    $actionLabel = $isOfficer ? 'Review' : ($isCashCertifier ? 'Certify' : 'Approve');
    $emptyLabel = $isCashCertifier ? 'No cash certification cases are waiting right now.' : 'No accounting cases are waiting right now.';
@endphp

<main class="p-8 max-w-7xl mx-auto space-y-6">
    <section class="rounded-[28px] bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(135deg,_#234E70_0%,_#18384f_46%,_#27597c_100%)] px-8 py-9 text-white shadow-[0_24px_60px_rgba(24,56,79,0.18)]">
        <div class="grid gap-6 lg:grid-cols-[1.4fr_.9fr] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                    {{ $workspaceLabel }}
                </span>
                <h1 class="mt-5 text-3xl font-bold leading-tight sm:text-4xl">{{ $queueTitle }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                    {{ $queueDescription }}
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
            <a href="{{ route($indexRoute, ['scope' => 'active']) }}"
               class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold {{ $isFinishedScope ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-[#234E70] text-white' }}">
                {{ $isOfficer ? 'For Review' : 'For Approval' }}
            </a>
            <a href="{{ route($indexRoute, ['scope' => 'finished']) }}"
               class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold {{ $isFinishedScope ? 'bg-[#234E70] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Completed
            </a>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route($indexRoute) }}" class="grid gap-4 md:grid-cols-4">
            <input type="hidden" name="scope" value="{{ $filters['scope'] ?? 'active' }}">
            <div>
                <label class="label">Search</label>
                <input type="text" name="search" class="input" value="{{ $filters['search'] }}" placeholder="Reference, client, provider">
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
                        @php
                            $paymentStatusLabel = match ($paymentStatus) {
                                'paid' => 'Paid',
                                'for_processing_program_approval' => 'For Processing (Program Approval)',
                                'for_processing_budget' => 'For Processing (Budget)',
                                'for_processing_accounting' => 'For Processing (Accounting)',
                                'for_processing_program_amount_approval' => 'For Processing (Program Amount Approval)',
                                'for_processing_cash' => 'For Processing (Cash)',
                                'for_processing_accounting_certification' => 'For Processing (Accounting Certification)',
                                'for_processing_finance_director' => 'For Processing (Finance Director)',
                                'for_compliance_service_provider' => 'For Compliance (Service Provider)',
                                'for_compliance_gl_processor' => 'For Compliance (GL Processor)',
                                'for_compliance_approving_officer' => 'For Compliance (Approving Officer)',
                                'for_compliance_budget_officer' => 'For Compliance (Budget Officer)',
                                'for_compliance_accounting_officer' => 'For Compliance (Accounting Officer)',
                                'for_compliance_cash_officer' => 'For Compliance (Cash Officer)',
                                default => ucwords(str_replace('_', ' ', $paymentStatus)),
                            };
                        @endphp
                        <option value="{{ $paymentStatus }}" @selected($filters['payment_status'] === $paymentStatus)>{{ $paymentStatusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route($indexRoute) }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Reference</th>
                        <th class="px-5 py-4">Client</th>
                        <th class="px-5 py-4">Provider</th>
                        <th class="px-5 py-4">Amount</th>
                        <th class="px-5 py-4">Fund Source</th>
                        <th class="w-[240px] px-5 py-4">Payment Status</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($applications as $application)
                        @php
                            $isHistoricalRow = match (true) {
                                $isCashCertifier => (int) ($application->gl_cash_certified_by ?? 0) === (int) auth()->id() && ! in_array($application->gl_cash_certification_status, ['pending_approval', null], true),
                                $isOfficer => (int) ($application->gl_accounting_reviewed_by ?? 0) === (int) auth()->id() && ! in_array($application->gl_accounting_review_status, ['pending_review', null], true),
                                default => (int) ($application->gl_accounting_approved_by ?? 0) === (int) auth()->id() && ! in_array($application->gl_accounting_approval_status, ['pending_approval', null], true),
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="w-[240px] px-5 py-4 align-middle">
                                <p class="font-semibold text-slate-900">{{ $application->reference_no }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $isHistoricalRow ? 'Handled' : ($isOfficer ? 'Approved' : ($isCashCertifier ? 'Cash Approved' : 'Reviewed')) }}
                                    {{ optional($isOfficer ? $application->gl_program_approved_at : ($isCashCertifier ? $application->gl_cash_approved_at : $application->gl_accounting_reviewed_at))->format('M d, Y h:i A') ?? $application->updated_at?->format('M d, Y h:i A') }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
                                @if($isOfficer && $application->gl_budget_remarks)
                                    <p class="mt-1 text-xs text-slate-500">Processor remarks: {{ \Illuminate\Support\Str::limit($application->gl_budget_remarks, 80) }}</p>
                                @endif
                                @if(! $isOfficer && ! $isCashCertifier && $application->gl_accounting_remarks)
                                    <p class="mt-1 text-xs text-slate-500">Accounting remarks: {{ \Illuminate\Support\Str::limit($application->gl_accounting_remarks, 80) }}</p>
                                @endif
                                @if($isCashCertifier && $application->gl_cash_approval_remarks)
                                    <p class="mt-1 text-xs text-slate-500">Cash approval remarks: {{ \Illuminate\Support\Str::limit($application->gl_cash_approval_remarks, 80) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $application->serviceProvider?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $application->gl_finance_fund_source ?? '-' }}</td>
                            <td class="w-[240px] px-5 py-4 align-middle">
                                @php
                                    $rowStatusLabel = match (true) {
                                        $application->gl_accounting_approval_status === 'disapproved' || $application->gl_cash_certification_status === 'disapproved' => 'Disapproved',
                                        $application->gl_payment_status === 'paid' => 'Paid',
                                        $application->gl_payment_status === 'for_processing_finance_director' => 'For Processing (Finance Director)',
                                        $application->gl_payment_status === 'for_compliance_accounting_officer' => 'For Compliance (Accounting Officer)',
                                        $application->gl_payment_status === 'for_compliance_cash_officer' => 'For Compliance (Cash Officer)',
                                        default => $statusLabel,
                                    };
                                    $rowStatusClass = match ($rowStatusLabel) {
                                        'Paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                        'Disapproved' => 'border-slate-300 bg-slate-100 text-slate-700',
                                        'For Compliance (Accounting Officer)',
                                        'For Compliance (Cash Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
                                        default => 'border-blue-200 bg-blue-50 text-blue-700',
                                    };
                                @endphp
                                <span class="inline-flex min-h-9 items-center justify-center rounded-full border px-3 py-1 text-center text-[11px] font-bold uppercase leading-tight tracking-[0.16em] {{ $rowStatusClass }}">
                                    {{ $rowStatusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route($showRoute, $application->id) }}"
                                   class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    {{ $isFinishedScope || $isHistoricalRow ? 'View' : $actionLabel }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                {{ $emptyLabel }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $applications->links() }}
        </div>
    </section>
</main>

@endsection
