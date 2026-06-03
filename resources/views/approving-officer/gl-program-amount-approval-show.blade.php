@extends('layouts.app')

@section('content')

@php
    $readOnlyBatchRecord = $readOnlyBatchRecord ?? false;
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount)
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
    $orsRoute = 'approving.gl-program-amount-approvals.ors';
    $dvRoute = 'approving.gl-program-amount-approvals.dv';
    $lddapAdaRoute = 'approving.gl-program-amount-approvals.lddap-ada';
@endphp

<main class="space-y-6" x-data="{ activeTab: 'client' }">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                @if($readOnlyBatchRecord)
                    <a href="{{ $readOnlyBatchBackUrl ?? route('approving.gl-program-amount-approvals.show', $batch->id) }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                        &larr; Back to Program Amount Approval Batch
                    </a>
                @else
                    <a href="{{ route('approving.gl-program-amount-approvals') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                        &larr; Back to Program Amount Approvals
                    </a>
                @endif
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Approving Officer</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $application->reference_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $readOnlyBatchRecord
                        ? 'Inspect this included GL record one by one, then return to the batch workspace to make the program amount approval decision.'
                        : 'Review the amount and final guarantee letter payment details after accounting approval.' }}
                </p>
            </div>
            @if($application->gl_ors_number || $application->gl_dv_number || $application->gl_lddap_ada_number)
                <div class="flex flex-wrap gap-3">
                    @if($application->gl_ors_number)
                        <a href="{{ route($orsRoute, $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            View ORS
                        </a>
                    @endif
                    @if($application->gl_dv_number)
                        <a href="{{ route($dvRoute, $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                            View DV
                        </a>
                    @endif
                    @if($application->gl_lddap_ada_number)
                        <a href="{{ route($lddapAdaRoute, $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
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

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Assistance Status</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">
                    {{ strtoupper(str_replace('_', ' ', $application->status)) }}
                </span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment Status</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-sky-700">
                    For Processing (Program Amount Approval)
                </span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Utilized Amount</p>
            <p class="mt-3 text-lg font-black text-slate-900">PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap gap-2">
            <button type="button" x-on:click="activeTab = 'client'" x-bind:class="activeTab === 'client' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Client Info</button>
            @if($application->beneficiary)
                <button type="button" x-on:click="activeTab = 'beneficiary'" x-bind:class="activeTab === 'beneficiary' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Beneficiary Info</button>
            @endif
            <button type="button" x-on:click="activeTab = 'assessment'" x-bind:class="activeTab === 'assessment' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Initial Assessment</button>
            <button type="button" x-on:click="activeTab = 'recommendation'" x-bind:class="activeTab === 'recommendation' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Recommendation</button>
            <button type="button" x-on:click="activeTab = 'attachments'" x-bind:class="activeTab === 'attachments' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Attachments</button>
            @unless($readOnlyBatchRecord)
                <button type="button" x-on:click="activeTab = 'decision'" x-bind:class="activeTab === 'decision' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Decision</button>
            @endunless
        </div>
    </section>

    <section x-show="activeTab === 'client'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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
        <section x-show="activeTab === 'beneficiary'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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

    <section x-show="activeTab === 'assessment'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-2xl font-black text-sky-950">Initial Assessment</h2>
        <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
            <div><span class="font-semibold text-slate-500">Assistance Type</span><br>{{ $application->assistanceType?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Subtype</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Mode</span><br>{{ $application->modeOfAssistance?->name ?? '-' }}</div>
        </div>
        <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Assessment Notes</span><br>{{ $application->notes ?: '-' }}</div>
    </section>

    <section x-show="activeTab === 'recommendation'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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

    <section x-show="activeTab === 'attachments'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                <h2 class="text-2xl font-black text-sky-950">Service Provider Attachments</h2>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-800">Updated Statement of Account</p>
                    <div class="mt-4 space-y-3">
                        @forelse($statementDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $document->file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ $document->created_at?->format('M d, Y h:i A') ?? '-' }}</p>
                                        @if($document->bankAccountSummary())
                                            <p class="mt-1 text-xs text-slate-500">Transfer account: {{ $document->bankAccountSummary() }}</p>
                                        @endif
                                        @if($document->remarks)
                                            <p class="mt-1 text-xs text-slate-500">{{ $document->remarks }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('documents.show', $document->id) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">View</a>
                                        <a href="{{ route('documents.download', $document->id) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No statement uploaded yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-800">Other Supporting Documents</p>
                    <div class="mt-4 space-y-3">
                        @forelse($supportingDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $document->file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ $document->created_at?->format('M d, Y h:i A') ?? '-' }}</p>
                                        @if($document->remarks)
                                            <p class="mt-1 text-xs text-slate-500">{{ $document->remarks }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('documents.show', $document->id) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">View</a>
                                        <a href="{{ route('documents.download', $document->id) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No supporting documents uploaded yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </section>

    @unless($readOnlyBatchRecord)
        <section x-show="activeTab === 'decision'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <h2 class="text-2xl font-black text-sky-950">Accounting Endorsement</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-800">Finance Fund Source</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $application->gl_finance_fund_source ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-800">Accounting Remarks</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $application->gl_accounting_remarks ?? 'No remarks added.' }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-sky-950">Decision</h2>
                    <form method="POST" action="{{ route('approving.gl-program-amount-approvals.update', $application->id) }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="label">Approval Decision</label>
                            <select name="decision" class="input">
                                <option value="for_compliance" @selected(old('decision') === 'for_compliance')>For Compliance</option>
                                <option value="approved" @selected(old('decision') === 'approved')>Approved</option>
                                <option value="disapproved" @selected(old('decision') === 'disapproved')>Disapproved</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Remarks / Reason</label>
                            <textarea name="remarks" class="input h-32" placeholder="Add remarks or the reason for disapproval when needed.">{{ old('remarks', $application->gl_program_amount_approval_remarks) }}</textarea>
                            <p class="mt-2 text-xs text-slate-500">Remarks are required when the decision is For Compliance or Disapproved.</p>
                        </div>
                        <button type="submit" class="btn-primary">Save Decision</button>
                    </form>
                </section>
            </div>
        </section>
    @endunless
</main>

@endsection
