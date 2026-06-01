@extends('layouts.app')

@section('content')

@php
    $hasBeneficiary = (bool) $application->beneficiary;
    $hasUpdatedStatement = $statementDocuments->isNotEmpty();
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
        'processing', 'for_processing' => 'For Processing',
        default => $hasUpdatedStatement ? 'For Processing' : 'Awaiting SOA',
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
                <a href="{{ route('service-provider.dashboard') }}" class="text-sm text-slate-500 hover:text-[#234E70]">
                    &larr; Back to Guarantee Letters
                </a>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Service Provider Case View</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">{{ $application->reference_no }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Review the approved case information, recommendation, and guarantee letter for {{ $provider->name }}.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                    View Guarantee Letter
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Assistance Status</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-700">
                    {{ strtoupper(str_replace('_', ' ', $application->status)) }}
                </span>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $paymentStatusBadgeClass }}">
                    {{ $paymentStatusLabel }}
                </span>
            </div>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap gap-2">
            <button type="button" x-on:click="activeTab = 'client'" x-bind:class="activeTab === 'client' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Client Information</button>
            @if($hasBeneficiary)
                <button type="button" x-on:click="activeTab = 'beneficiary'" x-bind:class="activeTab === 'beneficiary' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Beneficiary Information</button>
            @endif
            <button type="button" x-on:click="activeTab = 'assessment'" x-bind:class="activeTab === 'assessment' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Initial Assessment</button>
            <button type="button" x-on:click="activeTab = 'recommendation'" x-bind:class="activeTab === 'recommendation' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Recommendation</button>
            <button type="button" x-on:click="activeTab = 'attachments'" x-bind:class="activeTab === 'attachments' ? 'bg-[#234E70] text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-2xl px-4 py-2 text-sm font-semibold transition">Attachments</button>
        </div>
    </section>

    <section x-show="activeTab === 'client'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Case Information</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Client Information</h2>
        </div>

        <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
            <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->client?->last_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->client?->first_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->client?->middle_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Extension</span><br>{{ $application->client?->extension_name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Sex</span><br>{{ $application->client?->sex ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Birthdate</span><br>{{ $application->client?->birthdate ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Civil Status</span><br>{{ $application->client?->civil_status ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->client?->contact_number ?? '-' }}</div>
        </div>

        <div class="mt-4 text-sm">
            <span class="font-semibold text-slate-500">Address</span><br>
            {{ $application->client?->full_address ?? '-' }}
        </div>
    </section>

    @if($hasBeneficiary)
        <section x-show="activeTab === 'beneficiary'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Case Information</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Beneficiary Information</h2>
            </div>

            <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                <div><span class="font-semibold text-slate-500">Last Name</span><br>{{ $application->beneficiary?->last_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">First Name</span><br>{{ $application->beneficiary?->first_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Middle Name</span><br>{{ $application->beneficiary?->middle_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Extension</span><br>{{ $application->beneficiary?->extension_name ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Sex</span><br>{{ $application->beneficiary?->sex ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Birthdate</span><br>{{ $application->beneficiary?->birthdate ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Contact Number</span><br>{{ $application->beneficiary?->contact_number ?? '-' }}</div>
                <div><span class="font-semibold text-slate-500">Relationship to Client</span><br>{{ $application->beneficiary?->relationshipData?->name ?? '-' }}</div>
            </div>

            <div class="mt-4 text-sm">
                <span class="font-semibold text-slate-500">Address</span><br>
                {{ $application->beneficiary?->full_address ?? '-' }}
            </div>
        </section>
    @endif

    <section x-show="activeTab === 'assessment'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Assessment</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Initial Assessment</h2>
        </div>

        <div class="mt-6 grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
            <div><span class="font-semibold text-slate-500">Assistance Type</span><br>{{ $application->assistanceType?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Assistance Subtype</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Assistance Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Mode of Assistance</span><br>{{ $application->modeOfAssistance?->name ?? $application->mode_of_assistance ?? '-' }}</div>
            <div><span class="font-semibold text-slate-500">Amount Needed</span><br>PHP {{ number_format((float) ($application->amount_needed ?? 0), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Recommended Amount</span><br>PHP {{ number_format((float) ($application->recommended_amount ?? 0), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Final Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $totalRecommendedAmount), 2) }}</div>
            <div><span class="font-semibold text-slate-500">Schedule Date</span><br>{{ $application->schedule_date ? $application->schedule_date->format('M d, Y h:i A') : '-' }}</div>
        </div>

        <div class="mt-4 grid gap-4 text-sm md:grid-cols-2">
            <div>
                <span class="font-semibold text-slate-500">Assessment Notes</span><br>
                {{ $application->notes ?: '-' }}
            </div>
            <div>
                <span class="font-semibold text-slate-500">Meeting Link</span><br>
                {{ $application->meeting_link ?: '-' }}
            </div>
        </div>
    </section>

    <section x-show="activeTab === 'recommendation'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Recommendation</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Approved Recommendation</h2>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Assistance Recommendation</p>
                    <p class="mt-1 text-sm text-slate-500">Read-only view of the approved recommendation for this guarantee letter case.</p>
                </div>
                <div class="rounded-xl bg-blue-100 px-4 py-3 text-sm font-semibold text-blue-900">
                    Total Final Amount: PHP {{ number_format((float) $totalRecommendedAmount, 2) }}
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($application->assistanceRecommendations as $recommendation)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4">
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
                                <p class="mt-1 text-sm text-slate-500">
                                    Mode: {{ $recommendation->modeOfAssistance?->name ?? '-' }}
                                </p>
                                @if($recommendation->referralInstitution)
                                    <p class="mt-1 text-sm text-slate-500">Referral: {{ $recommendation->referralInstitution->name }}</p>
                                @endif
                                @if($recommendation->notes)
                                    <p class="mt-3 text-sm text-slate-600">{{ $recommendation->notes }}</p>
                                @endif
                            </div>

                            <div class="text-left md:text-right">
                                <p class="text-xs text-slate-500">Approved Amount</p>
                                <p class="text-lg font-black text-sky-950">PHP {{ number_format((float) $recommendation->final_amount, 2) }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-6 text-sm text-slate-500">
                        No recommendation items were recorded for this case.
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    <section x-show="activeTab === 'attachments'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Submission</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Attachments and Remarks</h2>
            <p class="mt-2 text-sm text-slate-500">
                Upload the updated statement, add supporting documents if needed, and include remarks before submitting.
            </p>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="font-semibold text-slate-800">Updated Statement of Account</p>
                    @if($application->gl_soa_review_notes)
                        <p class="mt-2 text-sm text-rose-700">
                            <span class="font-semibold">Return notes:</span> {{ $application->gl_soa_review_notes }}
                        </p>
                    @endif

                    <div class="mt-4 space-y-3">
                        @forelse($statementDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $document->file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ optional($document->created_at)->format('M d, Y h:i A') ?? '-' }}</p>
                                        @if($document->bankAccountSummary())
                                            <p class="mt-1 text-xs text-slate-500">Transfer account: {{ $document->bankAccountSummary() }}</p>
                                        @endif
                                        @if($document->remarks)
                                            <p class="mt-1 text-xs text-slate-500">{{ $document->remarks }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                        <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No statement uploaded yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="font-semibold text-slate-800">Other Supporting Documents</p>
                    <div class="mt-4 space-y-3">
                        @forelse($supportingDocuments as $document)
                            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $document->file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Uploaded {{ optional($document->created_at)->format('M d, Y h:i A') ?? '-' }}</p>
                                        @if($document->remarks)
                                            <p class="mt-1 text-xs text-slate-500">{{ $document->remarks }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                        <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center rounded-xl bg-[#234E70] px-3 py-2 text-xs font-semibold text-white hover:bg-[#18384f]">Download</a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No supporting documents uploaded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <form method="POST" action="{{ route('service-provider.attachments.submit', $application->id) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    @error('attachments')
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
                    @enderror

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Transfer Bank Account</p>
                                <p class="mt-1 text-xs text-slate-500">This account will be attached to the updated statement of account.</p>
                            </div>
                            <a href="{{ route('service-provider.bank-accounts') }}" class="text-sm font-semibold text-[#234E70] hover:text-[#18384f]">
                                Manage
                            </a>
                        </div>

                        <div class="mt-4">
                            <select name="service_provider_bank_account_id" class="input">
                                <option value="">Select bank account</option>
                                @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}" @selected((string) old('service_provider_bank_account_id', optional($defaultBankAccount)->id) === (string) $bankAccount->id)>
                                        {{ $bankAccount->displayLabel() }}{{ $bankAccount->is_default ? ' (Default)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_provider_bank_account_id')
                                <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label for="gl_actual_utilized_amount" class="text-sm font-semibold text-slate-800">Actual Utilized Amount</label>
                        <p class="mt-1 text-xs text-slate-500">This amount will be reflected in the ORS and DV.</p>
                        <input
                            id="gl_actual_utilized_amount"
                            type="number"
                            name="gl_actual_utilized_amount"
                            step="0.01"
                            min="0"
                            class="mt-3 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                            value="{{ old('gl_actual_utilized_amount', $application->gl_actual_utilized_amount ?? $application->final_amount ?? $application->recommended_amount) }}"
                            placeholder="0.00"
                        >
                        @error('gl_actual_utilized_amount')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="statement_file" class="text-sm font-semibold text-slate-800">Updated Statement of Account</label>
                        <input id="statement_file" type="file" name="statement_file" class="mt-3 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        @error('statement_file')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="supporting_document_file" class="text-sm font-semibold text-slate-800">Other Supporting Documents</label>
                        <input id="supporting_document_file" type="file" name="supporting_document_file" class="mt-3 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        @error('supporting_document_file')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attachment_remarks" class="text-sm font-semibold text-slate-800">Remarks</label>
                        <textarea id="attachment_remarks" name="attachment_remarks" rows="5" class="mt-3 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" placeholder="Add remarks for this submission...">{{ old('attachment_remarks') }}</textarea>
                        @error('attachment_remarks')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#234E70] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#18384f]">
                            Submit Attachments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

@endsection
