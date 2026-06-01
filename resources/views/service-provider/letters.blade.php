@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.45),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">Service Provider Workspace</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Assigned Guarantee Letters</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Review every assigned guarantee letter for {{ $provider->name }}, open case details, view the printed GL, and upload updated statements of account.
                </p>
            </div>

            <a href="{{ route('service-provider.dashboard') }}"
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
            <p class="font-semibold">Please review the uploaded statement details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Case Inventory</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">All Assigned Guarantee Letters</h2>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                {{ $applications->total() }} total case{{ $applications->total() === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="library-toolbar mt-6">
            <form method="GET" action="{{ route('service-provider.letters') }}" class="library-filter">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] }}"
                       class="input"
                       placeholder="Search guarantee letters">

                <select name="status" class="input">
                    <option value="all" @selected($filters['status'] === 'all')>All</option>
                    <option value="pending_upload" @selected($filters['status'] === 'pending_upload')>Pending Upload</option>
                    <option value="uploaded" @selected($filters['status'] === 'uploaded')>Uploaded</option>
                    <option value="awaiting_upload" @selected($filters['status'] === 'awaiting_upload')>Awaiting Upload</option>
                    <option value="pending_review" @selected($filters['status'] === 'pending_review')>Pending Review</option>
                    <option value="returned_for_compliance" @selected($filters['status'] === 'returned_for_compliance')>Returned</option>
                    <option value="processed" @selected($filters['status'] === 'processed')>Processed</option>
                </select>

                <button type="submit" class="btn-secondary">Filter</button>
            </form>

            <div class="library-status-strip">
                <span class="library-status-chip library-status-chip--active">
                    {{ $uploadedCount }} uploaded
                </span>
                <span class="library-status-chip library-status-chip--archived">
                    {{ $pendingUploadCount }} pending upload
                </span>
                @if($filters['status'] !== 'all')
                    <span class="library-status-note">
                        Filtered view for {{ ucwords(str_replace('_', ' ', $filters['status'])) }}.
                    </span>
                @endif
            </div>
        </div>

        <div class="table-shell mt-6">
            @forelse($applications as $application)
                @php
                    $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
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
                    $clientName = trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-';
                    $beneficiaryName = $application->beneficiary
                        ? trim(implode(' ', array_filter([
                            $application->beneficiary?->first_name,
                            $application->beneficiary?->middle_name,
                            $application->beneficiary?->last_name,
                            $application->beneficiary?->extension_name,
                        ])))
                        : '';
                    $beneficiaryRelationship = strtolower(trim((string) ($application->beneficiary?->relationshipData?->name ?? '')));
                    $showBeneficiary = $beneficiaryName !== '' && $beneficiaryRelationship !== 'self';
                @endphp
                @if($loop->first)
                    <table class="letter-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Client</th>
                                <th>Assistance</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                            <tr>
                                <td>
                                    <p class="table-primary">{{ $application->reference_no }}</p>
                                    <p class="table-secondary">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</p>
                                </td>
                                <td>
                                    <p class="table-primary">{{ $clientName }}</p>
                                    @if($showBeneficiary)
                                        <p class="table-secondary">Beneficiary: {{ $beneficiaryName }}</p>
                                    @endif
                                    <p class="table-secondary">{{ $application->client?->birthdate ? \Carbon\Carbon::parse($application->client->birthdate)->format('F d, Y') : 'No birthdate' }}</p>
                                </td>
                                <td>
                                    <p>{{ $application->assistanceSubtype?->name ?? '-' }}</p>
                                    <p class="table-secondary">{{ $application->assistanceDetail?->name ?? '-' }}</p>
                                </td>
                                <td>
                                    <p class="table-primary">PHP {{ number_format($application->effectiveDisplayedAmount(), 2) }}</p>
                                </td>
                                <td>
                                    <span class="status-pill {{ $application->gl_payment_status === 'paid' ? 'status-pill--emerald' : (in_array($application->gl_payment_status, ['for_compliance_gl_processor', 'for_compliance_service_provider', 'for_compliance_approving_officer', 'for_compliance_budget_officer', 'for_compliance_accounting_officer', 'for_compliance_cash_officer'], true) ? 'status-pill--rose' : (in_array($application->gl_payment_status, ['for_processing_cash', 'for_processing_accounting_certification', 'for_processing_finance_director', 'for_processing_program_amount_approval', 'for_processing_accounting', 'for_processing_budget', 'for_processing_program_approval'], true) ? 'status-pill--sky' : 'status-pill--slate')) }}">
                                        {{ $paymentStatusLabel }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions justify-end">
                                        <a href="{{ route('service-provider.show', $application->id) }}"
                                           class="action-icon action-icon--view"
                                           title="Open case workspace"
                                           aria-label="Open case workspace">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </a>
                                        <a href="{{ route('service-provider.guarantee-letter', $application->id) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="action-icon action-icon--document"
                                           title="Open guarantee letter"
                                           aria-label="Open guarantee letter">
                                            <span class="material-symbols-outlined text-[20px]">description</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                @if($loop->last)
                        </tbody>
                    </table>
                @endif
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    No approved guarantee letters are assigned to this service provider account yet.
                </div>
            @endforelse
        </div>

        @if($applications->hasPages())
            <div class="mt-5">
                {{ $applications->links() }}
            </div>
        @endif
    </section>
</main>

<style>
.panel-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}

.library-toolbar{
    display:flex;
    flex-direction:column;
    gap:16px;
}

.library-filter{
    display:grid;
    grid-template-columns:minmax(0,1fr) 180px auto;
    gap:12px;
}

.library-status-strip{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.library-status-chip{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.04em;
}

.library-status-chip--active{
    background:#ecfdf5;
    color:#166534;
    border:1px solid #bbf7d0;
}

.library-status-chip--archived{
    background:#f1f5f9;
    color:#475569;
    border:1px solid #cbd5e1;
}

.library-status-note{
    font-size:13px;
    color:#64748b;
}

.table-shell{
    overflow-x:auto;
    border-radius:18px;
    border:1px solid #e2e8f0;
}

.letter-table{
    width:100%;
    border-collapse:collapse;
}

.letter-table thead{
    background:#f8fafc;
}

.letter-table th,
.letter-table td{
    padding:16px 18px;
    text-align:left;
    border-bottom:1px solid #e2e8f0;
    vertical-align:top;
}

.letter-table th{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#64748b;
}

.table-primary{
    font-weight:700;
    color:#0f172a;
}

.table-secondary{
    margin-top:6px;
    color:#64748b;
    font-size:13px;
}

.table-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.action-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    border-radius:12px;
    box-shadow:0 1px 2px rgba(15, 23, 42, 0.08);
    transition:background-color .2s ease, color .2s ease, transform .2s ease;
}

.action-icon:hover{
    transform:translateY(-1px);
}

.action-icon--view{
    background:#e0f2fe;
    color:#075985;
}

.action-icon--document{
    background:#ffffff;
    color:#475569;
    border:1px solid #e2e8f0;
}

.status-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.04em;
}

.status-pill--emerald{
    background:#ecfdf5;
    color:#166534;
    border:1px solid #bbf7d0;
}

.status-pill--amber{
    background:#fffbeb;
    color:#92400e;
    border:1px solid #fde68a;
}

.status-pill--rose{
    background:#fef2f2;
    color:#b91c1c;
    border:1px solid #fecaca;
}

.status-pill--sky{
    background:#eff6ff;
    color:#1d4ed8;
    border:1px solid #bfdbfe;
}

.status-pill--slate{
    background:#f1f5f9;
    color:#475569;
    border:1px solid #cbd5e1;
}

.modal-shell{
    position:fixed;
    inset:0;
    z-index:60;
}

.modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15, 23, 42, .52);
}

.modal-card{
    position:relative;
    margin:40px auto;
    width:min(720px, calc(100% - 32px));
    max-height:calc(100vh - 80px);
    overflow:auto;
    border-radius:24px;
    background:#fff;
    box-shadow:0 30px 70px rgba(15, 23, 42, .28);
}

.modal-card--compact{
    width:min(560px, calc(100% - 32px));
}

.modal-head{
    display:flex;
    justify-content:space-between;
    gap:16px;
    padding:24px 24px 18px;
    border-bottom:1px solid #e2e8f0;
}

.modal-close{
    border-radius:999px;
    background:#f1f5f9;
    padding:10px 14px;
    font-weight:700;
    color:#475569;
}

.modal-copy{
    margin-top:8px;
    color:#64748b;
    font-size:14px;
}

.modal-form{
    padding:24px;
    display:flex;
    flex-direction:column;
    gap:18px;
}

.modal-grid{
    display:grid;
    gap:16px;
}

.modal-grid.two{
    grid-template-columns:repeat(2, minmax(0, 1fr));
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:8px;
    flex-wrap:wrap;
}

.btn-primary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:42px;
    padding:0 16px;
    border-radius:12px;
    background:#234E70;
    color:#ffffff;
    font-size:14px;
    font-weight:700;
}

.btn-primary:hover{
    background:#18384f;
}

.btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:42px;
    padding:0 16px;
    border-radius:12px;
    background:#f1f5f9;
    color:#475569;
    font-size:14px;
    font-weight:700;
}

@media (max-width: 900px){
    .library-filter{
        grid-template-columns:1fr;
    }

    .modal-grid.two{
        grid-template-columns:1fr;
    }
}
</style>

@endsection
