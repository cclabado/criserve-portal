@extends('layouts.app')

@section('content')

@php
    $latestStatement = $statementDocuments->first();
    $defaultMfoPap = str_contains(strtoupper((string) old('gl_finance_fund_source', $application->gl_finance_fund_source)), 'AKAP')
        ? '320104200006000'
        : '320104100001000';
    $totalRecommendedAmount = $application->assistanceRecommendations->isNotEmpty()
        ? $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount)
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
    $paymentStatusLabel = match ($application->gl_payment_status) {
        'paid' => 'Paid',
        'for_compliance_service_provider' => 'For Compliance (Service Provider)',
        'for_compliance_gl_processor' => 'For Compliance (GL Processor)',
        'for_compliance_approving_officer' => 'For Compliance (Approving Officer)',
        'for_compliance_budget_officer' => 'For Compliance (Budget Officer)',
        'for_compliance_accounting_officer' => 'For Compliance (Accounting Officer)',
        'for_compliance_cash_officer' => 'For Compliance (Cash Officer)',
        'for_processing_cash' => 'For Processing (Cash)',
        'for_processing_accounting_certification' => 'For Processing (Accounting Certification)',
        'for_processing_finance_director' => 'For Processing (Finance Director)',
        'for_processing_program_amount_approval' => 'For Processing (Program Amount Approval)',
        'for_processing_accounting' => 'For Processing (Accounting)',
        'for_processing_budget' => 'For Processing (Budget)',
        'for_processing_program_approval' => 'For Processing (Program Approval)',
        default => $latestStatement ? 'For Processing' : 'Awaiting SOA',
    };
    $paymentStatusBadgeClass = match ($paymentStatusLabel) {
        'Paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'For Compliance (Service Provider)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Compliance (GL Processor)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Compliance (Approving Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Compliance (Budget Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Compliance (Accounting Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Compliance (Cash Officer)' => 'border-rose-200 bg-rose-50 text-rose-700',
        'For Processing (Cash)' => 'border-blue-200 bg-blue-50 text-blue-700',
        'For Processing (Accounting Certification)' => 'border-blue-200 bg-blue-50 text-blue-700',
        'For Processing (Finance Director)' => 'border-blue-200 bg-blue-50 text-blue-700',
        'For Processing (Program Amount Approval)' => 'border-sky-200 bg-sky-50 text-sky-700',
        'For Processing (Accounting)' => 'border-amber-200 bg-amber-50 text-amber-700',
        'For Processing (Budget)' => 'border-violet-200 bg-violet-50 text-violet-700',
        'For Processing (Program Approval)' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'For Processing' => 'border-blue-200 bg-blue-50 text-blue-700',
        default => 'border-amber-200 bg-amber-50 text-amber-700',
    };
@endphp

<main class="space-y-6" x-data="{ activeTab: 'client' }">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('gl-payment-processor.queue') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                    &larr; Back to GL Queue
                </a>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">GL Payment Processor</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $application->reference_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Review the service provider attachments, return the case for compliance when needed, or submit it to the approving officer with a selected fund source.
                </p>
            </div>

            <a href="{{ route('gl-payment-processor.guarantee-letter', $application->id) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                View Guarantee Letter
            </a>
        </div>
    </section>

    @if($application->gl_ors_number || $application->gl_dv_number || $application->gl_lddap_ada_number)
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Generated Documents</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">ORS, DV, and LDDAP-ADA</h2>
                    <p class="mt-2 text-sm text-slate-500">ORS and DV are generated at GL processor submission, while LDDAP-ADA is generated once the cash officer reviews the case.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if($application->gl_ors_number)
                        <a href="{{ route('gl-payment-processor.ors', $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            View ORS
                        </a>
                    @endif
                    @if($application->gl_dv_number)
                        <a href="{{ route('gl-payment-processor.dv', $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                            View DV
                        </a>
                    @endif
                    @if($application->gl_lddap_ada_number)
                        <a href="{{ route('gl-payment-processor.lddap-ada', $application->id, false) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            View LDDAP-ADA
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($application->gl_payment_status === 'for_compliance_gl_processor' && $application->gl_program_approval_remarks)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Returned by Approving Officer for compliance.</p>
            <p class="mt-2">{{ $application->gl_program_approval_remarks }}</p>
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
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Application</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $paymentStatusBadgeClass }}">{{ $paymentStatusLabel }}</span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Provider</p><p class="mt-3 text-lg font-black text-slate-900">{{ $application->serviceProvider?->name ?? '-' }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Utilized Amount</p><p class="mt-3 text-lg font-black text-slate-900">PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Actual Utilized Amount</p><p class="mt-3 text-lg font-black text-slate-900">PHP {{ number_format((float) ($application->gl_actual_utilized_amount ?? 0), 2) }}</p></article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap gap-2">
            <button type="button" x-on:click="activeTab = 'client'" x-bind:class="activeTab === 'client' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Client Information</button>
            @if($application->beneficiary)
                <button type="button" x-on:click="activeTab = 'beneficiary'" x-bind:class="activeTab === 'beneficiary' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Beneficiary Information</button>
            @endif
            <button type="button" x-on:click="activeTab = 'assessment'" x-bind:class="activeTab === 'assessment' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Initial Assessment</button>
            <button type="button" x-on:click="activeTab = 'recommendation'" x-bind:class="activeTab === 'recommendation' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Recommendation</button>
            <button type="button" x-on:click="activeTab = 'attachments'" x-bind:class="activeTab === 'attachments' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Attachments</button>
            <button type="button" x-on:click="activeTab = 'actions'" x-bind:class="activeTab === 'actions' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Review Actions</button>
        </div>
    </section>

    <section x-show="activeTab === 'client'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Client Information</h2>
                <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->client?->last_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->client?->first_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->client?->middle_name ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->client?->contact_number ?? '-' }}</div>
                </div>
                <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Address</span><br>{{ $application->client?->full_address ?? '-' }}</div>
            </section>
        </div>
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
        <div class="mt-4 text-sm"><span class="font-semibold text-slate-500">Social Worker Assessment</span><br>{{ $application->social_worker_assessment ?: '-' }}</div>
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
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Uploaded Attachments</h2>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="font-semibold text-slate-800">Updated Statement of Account</p>
                    <div class="mt-4 space-y-3">
                        @forelse($statementDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
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
                                        <a href="{{ route('documents.show', $document->id) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">Review</a>
                                        <a href="{{ route('documents.download', $document->id) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-slate-600">No updated statement of account has been uploaded yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="font-semibold text-slate-800">Other Supporting Documents</p>
                    <div class="mt-4 space-y-3">
                        @forelse($supportingDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $document->file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ $document->created_at?->format('M d, Y h:i A') ?? '-' }}</p>
                                        @if($document->remarks)
                                            <p class="mt-1 text-xs text-slate-500">{{ $document->remarks }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('documents.show', $document->id) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">Review</a>
                                        <a href="{{ route('documents.download', $document->id) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-slate-600">No supporting documents uploaded yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </section>

    <section x-show="activeTab === 'actions'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Return for Compliance</h2>
                <form method="POST" action="{{ route('gl-payment-processor.soa-review.update', $application->id) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="label">Compliance Remarks</label>
                        <textarea name="gl_soa_review_notes" class="input h-32" placeholder="State missing attachments, incorrect files, or required corrections.">{{ old('gl_soa_review_notes', $application->gl_soa_review_notes) }}</textarea>
                        <p class="mt-2 text-xs text-slate-500">These remarks will be shown when the case is returned to the service provider.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Return for Compliance
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-sky-950">Submit for Approving Officer Review</h2>
                <form method="POST" action="{{ route('gl-payment-processor.budget-processing.submit', $application->id) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="label">Finance Fund Source</label>
                        <select name="gl_finance_fund_source" class="input">
                            <option value="">Select fund source</option>
                            @foreach($financeFundSources as $fundSource)
                                <option value="{{ $fundSource }}" @selected(old('gl_finance_fund_source', $application->gl_finance_fund_source) === $fundSource)>{{ $fundSource }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">Fund Cluster</label>
                            <input type="text" name="gl_fund_cluster" class="input" value="{{ old('gl_fund_cluster', $application->gl_fund_cluster ?: 'Regular Agency Fund') }}" placeholder="Regular Agency Fund">
                        </div>
                        <div>
                            <label class="label">Responsibility Center</label>
                            <input type="text" name="gl_responsibility_center" class="input" value="{{ old('gl_responsibility_center', $application->gl_responsibility_center ?: 'PMB-CID') }}" placeholder="PMB-CID">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">MFO/PAP</label>
                            <input type="text" name="gl_mfo_pap" class="input" value="{{ old('gl_mfo_pap', $application->gl_mfo_pap ?: $defaultMfoPap) }}" placeholder="320104100001000">
                            <p class="mt-2 text-xs text-slate-500">Default follows the ORS instruction: PSIF `320104100001000`, AKAP `320104200006000`.</p>
                        </div>
                        <div>
                            <label class="label">Mode of Payment</label>
                            <select name="gl_mode_of_payment" class="input">
                                @foreach(['ADA', 'Check', 'Cash'] as $paymentMode)
                                    <option value="{{ $paymentMode }}" @selected(old('gl_mode_of_payment', $application->gl_mode_of_payment ?: 'ADA') === $paymentMode)>{{ $paymentMode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label">Payee TIN</label>
                        <input type="text" name="gl_payee_tin" class="input" value="{{ old('gl_payee_tin', $application->gl_payee_tin) }}" placeholder="Optional TIN / taxpayer number of the service provider">
                    </div>
                    <div>
                        <label class="label">Remarks</label>
                        <textarea name="gl_budget_remarks" class="input h-32" placeholder="Optional processor remarks for approving officer or budget routing.">{{ old('gl_budget_remarks', $application->gl_budget_remarks) }}</textarea>
                    </div>
                    <button type="submit" class="btn-primary">Submit</button>
                </form>
            </section>

            @if($application->glSoaReviewer || $application->gl_soa_reviewed_at || $application->glBudgetReviewer || $application->gl_budget_reviewed_at)
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-sky-950">Review History</h2>
                    <div class="mt-4 text-sm text-slate-600 space-y-2">
                        <p><span class="font-semibold text-slate-800">Reviewed By:</span> {{ $application->glSoaReviewer?->name ?? 'Unknown reviewer' }}</p>
                        <p><span class="font-semibold text-slate-800">Reviewed At:</span> {{ $application->gl_soa_reviewed_at?->format('M d, Y h:i A') ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Finance Fund Source:</span> {{ $application->gl_finance_fund_source ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Actual Utilized Amount:</span> PHP {{ number_format((float) ($application->gl_actual_utilized_amount ?? 0), 2) }}</p>
                        <p><span class="font-semibold text-slate-800">Fund Cluster:</span> {{ $application->gl_fund_cluster ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Responsibility Center:</span> {{ $application->gl_responsibility_center ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">MFO/PAP:</span> {{ $application->gl_mfo_pap ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Mode of Payment:</span> {{ $application->gl_mode_of_payment ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Payee TIN:</span> {{ $application->gl_payee_tin ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">ORS No.:</span> {{ $application->gl_ors_number ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">DV No.:</span> {{ $application->gl_dv_number ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Budget Reviewed By:</span> {{ $application->glBudgetReviewer?->name ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Budget Reviewed At:</span> {{ $application->gl_budget_reviewed_at?->format('M d, Y h:i A') ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Budget Remarks:</span> {{ $application->gl_budget_remarks ?? '-' }}</p>
                        <p><span class="font-semibold text-slate-800">Approving Officer Remarks:</span> {{ $application->gl_program_approval_remarks ?? '-' }}</p>
                    </div>
                </section>
            @endif
        </div>
    </section>
</main>

@endsection
