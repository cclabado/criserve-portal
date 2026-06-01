@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.55),_transparent_30%),linear-gradient(135deg,_#ffffff_0%,_#eef6ff_52%,_#f8fafc_100%)] p-6 shadow-sm lg:p-8">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px] xl:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.24em] text-sky-700">Service Provider Workspace</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Guarantee Letter Dashboard</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Track assigned guarantee letters for {{ $provider->name }}, see which cases still need an SOA, and move faster on submissions that are already in processing.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('service-provider.letters') }}"
                       class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#18384f]">
                        Open Guarantee Letters
                    </a>
                    <a href="{{ route('service-provider.bank-accounts') }}"
                       class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Manage Bank Accounts
                    </a>
                    <a href="{{ route('service-provider.letters', ['status' => 'pending_upload']) }}"
                       class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Upload Pending Cases
                    </a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                <div class="rounded-3xl border border-sky-200 bg-white/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Assigned Exposure</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">PHP {{ number_format((float) $totalFinalAmount, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $totalAssigned }} total assigned case{{ $totalAssigned === 1 ? '' : 's' }}</p>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Payment Completion</p>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <p class="text-3xl font-black text-emerald-950">{{ $completionRate }}%</p>
                        <p class="text-sm font-semibold text-emerald-800">{{ $paidCount }} paid</p>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-emerald-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $completionRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Please review the submitted attachment details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Assigned Cases</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ $totalAssigned }}</p>
            <p class="mt-2 text-sm text-slate-500">All active guarantee letter records under this provider.</p>
        </article>
        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-amber-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">Awaiting SOA</span>
            <p class="mt-3 text-3xl font-black text-amber-950">{{ $pendingStatementCount }}</p>
            <p class="mt-2 text-sm text-amber-800">Cases that still need a submitted statement of account.</p>
        </article>
        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-blue-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">For Processing</span>
            <p class="mt-3 text-3xl font-black text-blue-950">{{ $forProcessingCount }}</p>
            <p class="mt-2 text-sm text-blue-800">Cases with an SOA already submitted and moving through review.</p>
        </article>
        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Paid</span>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $paidCount }}</p>
            <p class="mt-2 text-sm text-emerald-800">Completed payments recorded for this provider.</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Default Transfer Account</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Bank Account Used for SOA Submissions</h2>
            </div>
            <a href="{{ route('service-provider.bank-accounts') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Manage Accounts
            </a>
        </div>

        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-5">
            @if($defaultBankAccount)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">
                        Default
                    </span>
                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Active transfer target</span>
                </div>
                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $defaultBankAccount->resolvedBankName() }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $defaultBankAccount->account_name }}</p>
                <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                    <span>Account No.: {{ $defaultBankAccount->maskedAccountNumber() }}</span>
                    <span>Branch: {{ $defaultBankAccount->branch_name ?: 'Not specified' }}</span>
                </div>
            @else
                <p class="text-sm text-slate-500">No default bank account is set yet. Add one so your SOA uploads automatically carry the correct transfer account.</p>
            @endif
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,.7fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Action Queue</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">Cases That Need Attention</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Prioritized list of records that still need submission, revision, or processor follow-through.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    {{ $priorityApplications->count() }} active item{{ $priorityApplications->count() === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($priorityApplications as $application)
                    @php
                        $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
                        $queueLabel = match (true) {
                            $application->gl_soa_status === 'returned_for_compliance' => 'Returned for compliance',
                            ! $hasUpdatedStatement => 'Awaiting SOA',
                            default => 'For processing',
                        };
                        $statusTone = match (true) {
                            $application->gl_soa_status === 'returned_for_compliance' => 'border-rose-200 bg-rose-50 text-rose-800',
                            ! $hasUpdatedStatement => 'border-amber-200 bg-amber-50 text-amber-800',
                            default => 'border-blue-200 bg-blue-50 text-blue-800',
                        };
                        $clientName = trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-';
                    @endphp

                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $statusTone }}">
                                        {{ $queueLabel }}
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $application->reference_no }}</span>
                                </div>

                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $clientName }}</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $application->assistanceSubtype?->name ?? 'Guarantee letter case' }}
                                    @if($application->assistanceDetail)
                                        • {{ $application->assistanceDetail->name }}
                                    @endif
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-700">
                                    Amount: PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('service-provider.show', $application->id) }}"
                                   class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    Open Case
                                </a>
                                <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                    View GL
                                </a>
                            </div>
                        </div>

                        @if($application->gl_soa_review_notes)
                            <div class="mt-4 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-rose-700">
                                <span class="font-semibold">Review notes:</span> {{ $application->gl_soa_review_notes }}
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No cases are waiting for action right now.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Workflow Pulse</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Operational Snapshot</h2>

                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold text-slate-800">SOA Submitted</p>
                            <p class="text-sm font-black text-slate-950">{{ $submittedStatementCount }}/{{ $totalAssigned }}</p>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-[#234E70]" style="width: {{ $totalAssigned > 0 ? round(($submittedStatementCount / $totalAssigned) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                            <span class="inline-flex rounded-full border border-blue-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">Pending Review</span>
                            <p class="mt-2 text-2xl font-black text-blue-950">{{ $pendingReviewCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <span class="inline-flex rounded-full border border-rose-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-rose-700">Needs Revision</span>
                            <p class="mt-2 text-2xl font-black text-rose-950">{{ $returnedCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Processed</span>
                            <p class="mt-2 text-2xl font-black text-emerald-950">{{ $processedCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">Released</span>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ $releasedCount }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Recently Cleared</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Processed Cases</h2>

                <div class="mt-5 space-y-3">
                    @forelse($recentlyProcessed as $application)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $application->reference_no }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700">
                                    Processed
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-700">
                                    PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}
                                </p>
                                <a href="{{ route('service-provider.show', $application->id) }}" class="text-sm font-semibold text-[#234E70] hover:text-[#18384f]">
                                    View Case
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No processed guarantee letter cases yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    </section>
</main>

@endsection
