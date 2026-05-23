@extends('layouts.app')

@section('content')

@php
    $isOfficer = $workspace === 'officer';
    $workspaceLabel = $isOfficer ? 'Cash Officer' : 'Cash Approver';
    $indexRoute = $isOfficer ? 'cash-officer.gl-payment-reviews' : 'cash-approver.gl-payment-approvals';
    $showRoute = $indexRoute.'.show';
    $statusLabel = 'For Processing (Cash)';
@endphp

<main class="p-8 max-w-7xl mx-auto space-y-6">
    <section class="rounded-[28px] bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(135deg,_#234E70_0%,_#18384f_46%,_#27597c_100%)] px-8 py-9 text-white shadow-[0_24px_60px_rgba(24,56,79,0.18)]">
        <div class="grid gap-6 lg:grid-cols-[1.4fr_.9fr] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90">
                    {{ $workspaceLabel }}
                </span>
                <h1 class="mt-5 text-3xl font-bold leading-tight sm:text-4xl">{{ $isOfficer ? 'Cash Review Queue' : 'Cash Approval Queue' }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                    {{ $isOfficer
                        ? 'Review guarantee letter cases already cleared for program amount approval before sending them to cash approval.'
                        : 'Approve cash-reviewed guarantee letter cases while keeping them in the cash processing stage.' }}
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

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route($indexRoute) }}" class="grid gap-4 md:grid-cols-3">
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
                        <th class="px-5 py-4">Payment Status</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($applications as $application)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $application->reference_no }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $isOfficer ? 'Program amount approved' : 'Cash reviewed' }}
                                    {{ optional($isOfficer ? $application->gl_program_amount_approved_at : $application->gl_cash_reviewed_at)->format('M d, Y h:i A') ?? $application->updated_at?->format('M d, Y h:i A') }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
                                @if($isOfficer && $application->gl_accounting_remarks)
                                    <p class="mt-1 text-xs text-slate-500">Accounting remarks: {{ \Illuminate\Support\Str::limit($application->gl_accounting_remarks, 80) }}</p>
                                @endif
                                @if(! $isOfficer && $application->gl_cash_remarks)
                                    <p class="mt-1 text-xs text-slate-500">Cash remarks: {{ \Illuminate\Support\Str::limit($application->gl_cash_remarks, 80) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $application->serviceProvider?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $application->gl_finance_fund_source ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route($showRoute, $application->id) }}"
                                   class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    {{ $isOfficer ? 'Review' : 'Approve' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                No cash cases are waiting right now.
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
