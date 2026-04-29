@extends('layouts.app')

@section('content')

@php
    $subtypeOptions = $formOptions['assistanceSubtypes']->map(fn ($subtype) => [
        'id' => $subtype->id,
        'type_id' => $subtype->assistance_type_id,
        'label' => ($subtype->type?->name ? $subtype->type->name.' - ' : '').$subtype->name,
        'name' => $subtype->name,
    ])->values();

    $detailOptions = $formOptions['assistanceDetails']->map(fn ($detail) => [
        'id' => $detail->id,
        'subtype_id' => $detail->assistance_subtype_id,
        'label' => ($detail->subtype?->type?->name ? $detail->subtype->type->name.' - ' : '').($detail->subtype?->name ? $detail->subtype->name.' - ' : '').$detail->name,
        'name' => $detail->name,
    ])->values();
@endphp

<main class="space-y-6">
    <section class="libraries-hero">
        <div>
            <p class="libraries-kicker">Administrator</p>
            <h1 class="libraries-title">Frequency Rules</h1>
            <p class="libraries-copy">Set up how often each assistance subtype or detail can be availed, including month intervals, incident-based restrictions, and override guidance for social workers.</p>
        </div>

        <div class="libraries-hero-actions">
            <a href="{{ route('admin.dashboard') }}" class="libraries-hero-button libraries-hero-button--secondary">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                Dashboard
            </a>

            <button type="button" id="openCreateFrequencyModalBtn" class="libraries-hero-button libraries-hero-button--primary">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Frequency Rule
            </button>
        </div>
    </section>

    @if(session('success'))
        <div class="libraries-alert libraries-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="libraries-alert libraries-alert--error">
            <p class="font-semibold">Please review the submitted frequency rule values.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="library-toolbar">
            <div class="rule-callout">
                <span class="material-symbols-outlined text-[18px]">info</span>
                <p>Subtype-level rules apply by default. If a detail-specific rule exists, it overrides the subtype rule for that assistance detail.</p>
            </div>

            <form method="GET" action="{{ route('admin.frequency-rules') }}" class="library-filter">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] }}"
                       class="input"
                       placeholder="Search by type, subtype, detail, rule, or note">

                <div></div>

                <button type="submit" class="btn-secondary">Filter</button>
            </form>
        </div>

        <div class="table-shell mt-6">
            <table class="library-table">
                <thead>
                    <tr>
                        <th>Assistance Type</th>
                        <th>Subtype / Detail</th>
                        <th>Rule</th>
                        <th>Interval</th>
                        <th>Flags</th>
                        <th>Notes</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        @php
                            $editPayload = [
                                'id' => $rule->id,
                                'assistance_type_id' => $rule->subtype?->assistance_type_id,
                                'assistance_subtype_id' => $rule->assistance_subtype_id,
                                'assistance_detail_id' => $rule->assistance_detail_id,
                                'rule_type' => $rule->rule_type,
                                'interval_months' => $rule->interval_months,
                                'requires_reference_date' => (bool) $rule->requires_reference_date,
                                'requires_case_key' => (bool) $rule->requires_case_key,
                                'allows_exception_request' => (bool) $rule->allows_exception_request,
                                'notes' => $rule->notes,
                            ];
                            $flagLabels = collect([
                                $rule->requires_reference_date ? 'Reference date required' : null,
                                $rule->requires_case_key ? 'Case key required' : null,
                                $rule->allows_exception_request ? 'Allows override request' : null,
                            ])->filter()->values();
                        @endphp
                        <tr>
                            <td>
                                <p class="table-primary">{{ $rule->subtype?->type?->name ?? '-' }}</p>
                            </td>
                            <td>
                                <p class="table-primary">{{ $rule->subtype?->name ?? '-' }}</p>
                                <p class="table-secondary">{{ $rule->detail?->name ? 'Detail: '.$rule->detail->name : 'Subtype-level rule' }}</p>
                            </td>
                            <td>
                                <span class="rule-pill">{{ $ruleTypes[$rule->rule_type] ?? str_replace('_', ' ', $rule->rule_type) }}</span>
                            </td>
                            <td>{{ $rule->interval_months ? $rule->interval_months.' month(s)' : '-' }}</td>
                            <td>
                                @if($flagLabels->isNotEmpty())
                                    <div class="flag-stack">
                                        @foreach($flagLabels as $flag)
                                            <span class="flag-pill">{{ $flag }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-slate-500">No extra flags</span>
                                @endif
                            </td>
                            <td>
                                <p class="table-note">{{ $rule->notes ?: 'No notes recorded.' }}</p>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <button type="button"
                                            class="table-action table-action--edit"
                                            data-edit-button
                                            data-id="{{ $rule->id }}"
                                            data-record='@json($editPayload)'>
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('admin.frequency-rules.destroy', $rule) }}"
                                          class="inline-flex"
                                          onsubmit="return confirm('Delete this frequency rule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action table-action--archive">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                No frequency rules found for the current search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $rules->links() }}
        </div>
    </section>
</main>

<div id="createFrequencyModal" class="modal-shell hidden">
    <div class="modal-backdrop" data-close-create-modal></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <p class="panel-kicker">Create</p>
                <h2 class="panel-title">Add Frequency Rule</h2>
            </div>
            <button type="button" class="modal-close" data-close-create-modal>Close</button>
        </div>

        <form method="POST" action="{{ route('admin.frequency-rules.store') }}" class="modal-form" id="createFrequencyForm">
            @csrf
            @include('admin.partials.frequency-rule-form-fields', ['formOptions' => $formOptions, 'ruleTypes' => $ruleTypes, 'prefix' => 'create'])

            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-create-modal>Cancel</button>
                <button type="submit" class="btn-primary">Save Frequency Rule</button>
            </div>
        </form>
    </div>
</div>

<div id="editFrequencyModal" class="modal-shell hidden">
    <div class="modal-backdrop" data-close-edit-modal></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <p class="panel-kicker">Update</p>
                <h2 class="panel-title">Edit Frequency Rule</h2>
            </div>
            <button type="button" class="modal-close" data-close-edit-modal>Close</button>
        </div>

        <form method="POST" id="editFrequencyForm" action="{{ route('admin.frequency-rules.update', 0) }}" class="modal-form">
            @csrf
            @method('PATCH')
            @include('admin.partials.frequency-rule-form-fields', ['formOptions' => $formOptions, 'ruleTypes' => $ruleTypes, 'prefix' => 'edit'])

            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-edit-modal>Cancel</button>
                <button type="submit" class="btn-primary">Update Frequency Rule</button>
            </div>
        </form>
    </div>
</div>

<style>
.libraries-hero{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:16px;
    padding:28px 30px;
    border-radius:24px;
    background:
        radial-gradient(circle at top right, rgba(149, 204, 170, .32), transparent 30%),
        linear-gradient(135deg, #ffffff 0%, #edf7f2 100%);
    border:1px solid #dcece3;
}
.libraries-hero-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}
.libraries-kicker,
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.libraries-title{
    margin-top:10px;
    font-size:34px;
    font-weight:900;
    color:#163750;
}
.libraries-copy{
    margin-top:10px;
    color:#64748b;
    max-width:780px;
}
.libraries-hero-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:58px;
    padding:0 22px;
    border-radius:14px;
    font-weight:700;
    white-space:nowrap;
}
.libraries-hero-button--secondary{
    background:#234E70;
    color:#fff;
}
.libraries-hero-button--primary{
    background:#2d5b84;
    color:#fff;
}
.libraries-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.libraries-alert--success{
    background:#ecfdf5;
    color:#166534;
    border-color:#bbf7d0;
}
.libraries-alert--error{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}
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
.rule-callout{
    display:flex;
    gap:10px;
    align-items:flex-start;
    padding:14px 16px;
    border-radius:18px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    color:#475569;
}
.table-shell{
    overflow:hidden;
    border-radius:18px;
    border:1px solid #e2e8f0;
}
.library-table{
    width:100%;
    border-collapse:collapse;
}
.library-table thead{
    background:#f8fafc;
}
.library-table th,
.library-table td{
    padding:16px 18px;
    text-align:left;
    border-bottom:1px solid #e2e8f0;
    vertical-align:top;
}
.library-table th{
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
.table-secondary,
.table-note{
    margin-top:6px;
    color:#64748b;
    font-size:13px;
}
.rule-pill,
.flag-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    font-weight:700;
}
.rule-pill{
    background:#dbeafe;
    color:#1d4ed8;
}
.flag-pill{
    background:#eef2ff;
    color:#4338ca;
}
.flag-stack{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}
.table-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
}
.table-action{
    border-radius:12px;
    padding:8px 12px;
    font-size:13px;
    font-weight:700;
}
.table-action--edit{
    background:#e0f2fe;
    color:#075985;
}
.table-action--archive{
    background:#fef2f2;
    color:#b91c1c;
}
.empty-state{
    text-align:center;
    color:#64748b;
    font-size:14px;
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
    width:min(760px, calc(100% - 32px));
    max-height:calc(100vh - 80px);
    overflow:auto;
    border-radius:24px;
    background:#fff;
    box-shadow:0 30px 70px rgba(15, 23, 42, .28);
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
}
.checkbox-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:12px;
}
.checkbox-card{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:14px;
    border:1px solid #dbe7f0;
    border-radius:16px;
    background:#f8fafc;
}
.checkbox-card input{
    width:auto;
    margin-top:3px;
}
@media (max-width: 900px){
    .library-filter,
    .modal-grid.two,
    .checkbox-grid{
        grid-template-columns:1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const createModal = document.getElementById('createFrequencyModal');
    const editModal = document.getElementById('editFrequencyModal');
    const openCreateBtn = document.getElementById('openCreateFrequencyModalBtn');
    const editButtons = document.querySelectorAll('[data-edit-button]');
    const editForm = document.getElementById('editFrequencyForm');
    const subtypeOptions = @json($subtypeOptions);
    const detailOptions = @json($detailOptions);
    const createOldRecord = @json([
        'assistance_subtype_id' => old('assistance_subtype_id'),
        'assistance_detail_id' => old('assistance_detail_id'),
    ]);
    const monthRuleTypes = ['every_n_months', 'every_n_months_review'];

    const toggleModal = (modal, open) => {
        if (!modal) return;
        modal.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
    };

    const updateDependentFields = (prefix, payload = null) => {
        const form = document.getElementById(`${prefix}FrequencyForm`);
        if (!form) return;

        const typeSelect = form.querySelector('[name="assistance_type_id"]');
        const subtypeSelect = form.querySelector('[name="assistance_subtype_id"]');
        const detailSelect = form.querySelector('[name="assistance_detail_id"]');
        const ruleTypeSelect = form.querySelector('[name="rule_type"]');
        const intervalWrap = form.querySelector('[data-interval-wrap]');
        const selectedTypeId = typeSelect?.value || '';
        const selectedSubtypeId = payload?.assistance_subtype_id ?? subtypeSelect?.value ?? '';
        const selectedDetailId = payload?.assistance_detail_id ?? detailSelect?.value ?? '';

        if (subtypeSelect) {
            const currentSubtypeValue = String(selectedSubtypeId || '');
            subtypeSelect.innerHTML = '<option value="">Select subtype</option>';

            subtypeOptions
                .filter((option) => !selectedTypeId || String(option.type_id) === String(selectedTypeId))
                .forEach((option) => {
                    const element = document.createElement('option');
                    element.value = option.id;
                    element.textContent = option.label;
                    if (String(option.id) === currentSubtypeValue) {
                        element.selected = true;
                    }
                    subtypeSelect.appendChild(element);
                });
        }

        const effectiveSubtypeId = subtypeSelect?.value || '';

        if (detailSelect) {
            const currentDetailValue = String(selectedDetailId || '');
            detailSelect.innerHTML = '<option value="">Subtype-level only</option>';

            detailOptions
                .filter((option) => !effectiveSubtypeId || String(option.subtype_id) === String(effectiveSubtypeId))
                .forEach((option) => {
                    const element = document.createElement('option');
                    element.value = option.id;
                    element.textContent = option.label;
                    if (String(option.id) === currentDetailValue) {
                        element.selected = true;
                    }
                    detailSelect.appendChild(element);
                });
        }

        if (intervalWrap && ruleTypeSelect) {
            intervalWrap.style.display = monthRuleTypes.includes(ruleTypeSelect.value) ? 'block' : 'none';
        }
    };

    openCreateBtn?.addEventListener('click', () => {
        updateDependentFields('create');
        toggleModal(createModal, true);
    });

    document.querySelectorAll('[data-close-create-modal]').forEach((element) => {
        element.addEventListener('click', () => toggleModal(createModal, false));
    });

    document.querySelectorAll('[data-close-edit-modal]').forEach((element) => {
        element.addEventListener('click', () => toggleModal(editModal, false));
    });

    ['create', 'edit'].forEach((prefix) => {
        const form = document.getElementById(`${prefix}FrequencyForm`);
        if (!form) return;

        form.querySelector('[name="assistance_type_id"]')?.addEventListener('change', () => updateDependentFields(prefix));
        form.querySelector('[name="assistance_subtype_id"]')?.addEventListener('change', () => updateDependentFields(prefix));
        form.querySelector('[name="rule_type"]')?.addEventListener('change', () => updateDependentFields(prefix));

        updateDependentFields(prefix, prefix === 'create' ? createOldRecord : null);
    });

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            const id = button.dataset.id;

            if (!editForm || !id) {
                return;
            }

            editForm.action = @json(route('admin.frequency-rules.update', ['frequencyRule' => '__ITEM__'])).replace('__ITEM__', id);

            editForm.querySelector('[name="assistance_type_id"]').value = record.assistance_type_id ?? '';
            editForm.querySelector('[name="rule_type"]').value = record.rule_type ?? '';
            editForm.querySelector('[name="interval_months"]').value = record.interval_months ?? '';
            editForm.querySelector('[name="notes"]').value = record.notes ?? '';
            editForm.querySelector('[name="requires_reference_date"]').checked = !!record.requires_reference_date;
            editForm.querySelector('[name="requires_case_key"]').checked = !!record.requires_case_key;
            editForm.querySelector('[name="allows_exception_request"]').checked = !!record.allows_exception_request;

            updateDependentFields('edit', record);

            const subtypeField = editForm.querySelector('[name="assistance_subtype_id"]');
            const detailField = editForm.querySelector('[name="assistance_detail_id"]');

            if (subtypeField) {
                subtypeField.value = record.assistance_subtype_id ?? '';
            }

            updateDependentFields('edit', record);

            if (detailField) {
                detailField.value = record.assistance_detail_id ?? '';
            }

            toggleModal(editModal, true);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            toggleModal(createModal, false);
            toggleModal(editModal, false);
        }
    });

    @if($errors->any())
        updateDependentFields('create', createOldRecord);
        toggleModal(createModal, true);
    @endif
});
</script>

@endsection
