@extends('layouts.app')

@section('content')

@php
    $statusPillClass = fn (bool $isActive) => $isActive
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-slate-200 text-slate-700';
    $activeCount = method_exists($items, 'getCollection')
        ? $items->getCollection()->where('is_active', true)->count()
        : collect($items)->where('is_active', true)->count();
    $archivedCount = method_exists($items, 'getCollection')
        ? $items->getCollection()->where('is_active', false)->count()
        : collect($items)->where('is_active', false)->count();

    $libraryStoreRoutes = [
        'assistance-types' => route('admin.libraries.assistance-types.store'),
        'assistance-subtypes' => route('admin.libraries.assistance-subtypes.store'),
        'assistance-details' => route('admin.libraries.assistance-details.store'),
        'document-requirements' => route('admin.libraries.document-requirements.store'),
        'modes-of-assistance' => route('admin.libraries.modes-of-assistance.store'),
        'service-points' => route('admin.libraries.service-points.store'),
        'service-providers' => route('admin.libraries.service-providers.store'),
        'positions' => route('admin.libraries.positions.store'),
        'relationships' => route('admin.libraries.relationships.store'),
        'client-types' => route('admin.libraries.client-types.store'),
        'referral-institutions' => route('admin.libraries.referral-institutions.store'),
    ];
@endphp

<main class="space-y-6">

    <section class="libraries-hero">
        <div>
            <p class="libraries-kicker">Administrator</p>
            <h1 class="libraries-title">{{ $definition['title'] }}</h1>
            <p class="libraries-copy">{{ $definition['description'] }}</p>
        </div>

        <div class="libraries-hero-actions">
            <a href="{{ route('admin.dashboard') }}" class="libraries-hero-button libraries-hero-button--secondary">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                Dashboard
            </a>

            <button type="button" id="openCreateLibraryModalBtn" class="libraries-hero-button libraries-hero-button--primary">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add {{ $definition['singular'] }}
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
            <p class="font-semibold">Please review the submitted library values.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="library-toolbar">
            <form method="GET" action="{{ route('admin.libraries.show', $definition['key']) }}" class="library-filter">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] }}"
                       class="input"
                       placeholder="Search {{ strtolower($definition['title']) }}">

                <select name="status" class="input">
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="archived" @selected($filters['status'] === 'archived')>Archived</option>
                    <option value="all" @selected($filters['status'] === 'all')>All</option>
                </select>

                <button type="submit" class="btn-secondary">Filter</button>
            </form>

            <div class="library-status-strip">
                <span class="library-status-chip library-status-chip--active">
                    {{ $activeCount }} active
                </span>
                <span class="library-status-chip library-status-chip--archived">
                    {{ $archivedCount }} archived
                </span>
                @if($filters['status'] === 'archived')
                    <span class="library-status-note">
                        Archived items are read-only and kept for historical alignment.
                    </span>
                @elseif($filters['status'] === 'all' && $archivedCount > 0)
                    <span class="library-status-note">
                        Archived rows are shaded so retired values stay easy to spot.
                    </span>
                @endif
            </div>
        </div>

        <div class="table-shell mt-6">
            <table class="library-table">
                <thead>
                    <tr>
                        @if($definition['key'] === 'assistance-subtypes')
                            <th>Subtype</th>
                            <th>Parent Type</th>
                        @elseif($definition['key'] === 'assistance-details')
                            <th>Detail</th>
                            <th>Subtype</th>
                            <th>Type</th>
                        @elseif($definition['key'] === 'document-requirements')
                            <th>Requirement</th>
                            <th>Applies To</th>
                            <th>Rule</th>
                        @elseif($definition['key'] === 'modes-of-assistance')
                            <th>Mode</th>
                            <th>Amount Rule</th>
                        @elseif($definition['key'] === 'service-providers')
                            <th>Provider</th>
                            <th>Categories</th>
                            <th>Addressee</th>
                            <th>Contact</th>
                            <th>Accounts</th>
                        @elseif($definition['key'] === 'positions')
                            <th>Position</th>
                            <th>Code</th>
                            <th>Salary Grade</th>
                            <th>License Rule</th>
                        @elseif($definition['key'] === 'referral-institutions')
                            <th>Institution</th>
                            <th>Addressee</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Accounts</th>
                        @else
                            <th>Name</th>
                        @endif
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $editPayload = [
                                'id' => $item->id,
                                'name' => $item->name,
                                'assistance_type_id' => $item->assistance_type_id ?? null,
                                'assistance_subtype_id' => $item->assistance_subtype_id ?? null,
                                'assistance_detail_id' => $item->assistance_detail_id ?? null,
                                'addressee' => $item->addressee ?? null,
                                'categories' => $item->categories ?? [],
                                'address' => $item->address ?? null,
                                'email' => $item->email ?? null,
                                'contact_number' => $item->contact_number ?? null,
                                'description' => $item->description ?? null,
                                'is_required' => (bool) ($item->is_required ?? false),
                                'applies_when_amount_exceeds' => $item->applies_when_amount_exceeds ?? null,
                                'minimum_amount' => $item->minimum_amount ?? null,
                                'maximum_amount' => $item->maximum_amount ?? null,
                                'position_code' => $item->position_code ?? null,
                                'salary_grade' => $item->salary_grade ?? null,
                                'requires_license_number' => (int) ($item->requires_license_number ?? false),
                                'sort_order' => $item->sort_order ?? 0,
                                'is_active' => (bool) $item->is_active,
                                'password' => null,
                            ];
                        @endphp
                        <tr class="{{ $item->is_active ? '' : 'library-row--archived' }}">
                            @if($definition['key'] === 'assistance-subtypes')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived library value</p>
                                    @endunless
                                </td>
                                <td>{{ $item->type?->name ?? '-' }}</td>
                            @elseif($definition['key'] === 'assistance-details')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived detail kept for historical records</p>
                                    @endunless
                                </td>
                                <td>{{ $item->subtype?->name ?? '-' }}</td>
                                <td>{{ $item->subtype?->type?->name ?? '-' }}</td>
                            @elseif($definition['key'] === 'document-requirements')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @if($item->description)
                                        <p class="table-secondary">{{ $item->description }}</p>
                                    @endif
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived requirement</p>
                                    @endunless
                                </td>
                                <td>
                                    <p>{{ $item->subtype?->type?->name ?? '-' }} - {{ $item->subtype?->name ?? '-' }}</p>
                                    <p class="table-secondary">{{ $item->detail?->name ? 'Detail: '.$item->detail->name : 'Subtype-wide requirement' }}</p>
                                </td>
                                <td>
                                    <p>{{ $item->is_required ? 'Required' : 'Optional' }}</p>
                                    <p class="table-secondary">
                                        {{ $item->applies_when_amount_exceeds !== null ? 'When amount exceeds P'.number_format((float) $item->applies_when_amount_exceeds, 2) : 'Always applies' }}
                                    </p>
                                </td>
                            @elseif($definition['key'] === 'modes-of-assistance')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived library value</p>
                                    @endunless
                                </td>
                                <td>
                                    <p>
                                        {{ $item->minimum_amount !== null ? 'Min: PHP '.number_format((float) $item->minimum_amount, 2) : 'No minimum' }}
                                    </p>
                                    <p class="table-secondary">
                                        {{ $item->maximum_amount !== null ? 'Max: PHP '.number_format((float) $item->maximum_amount, 2) : 'No maximum' }}
                                    </p>
                                </td>
                            @elseif($definition['key'] === 'service-providers')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @if($item->address)
                                        <p class="table-secondary">{{ $item->address }}</p>
                                    @endif
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived provider</p>
                                    @endunless
                                </td>
                                <td>
                                    @if(!empty($item->categories))
                                        <p>{{ implode(', ', $item->categories) }}</p>
                                    @else
                                        <p class="table-secondary">No categories assigned</p>
                                    @endif
                                </td>
                                <td>{{ $item->addressee ?: '-' }}</td>
                                <td>{{ $item->contact_number ?: '-' }}</td>
                                <td>{{ $item->accounts?->count() ?? 0 }} linked account(s)</td>
                            @elseif($definition['key'] === 'positions')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived position title</p>
                                    @endunless
                                </td>
                                <td>{{ $item->position_code ?: '-' }}</td>
                                <td>{{ $item->salary_grade ? 'SG '.$item->salary_grade : '-' }}</td>
                                <td>{{ $item->requires_license_number ? 'License required' : 'No license required' }}</td>
                            @elseif($definition['key'] === 'referral-institutions')
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @if($item->address)
                                        <p class="table-secondary">{{ $item->address }}</p>
                                    @endif
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived institution</p>
                                    @endunless
                                </td>
                                <td>{{ $item->addressee ?: '-' }}</td>
                                <td>{{ $item->contact_number ?: '-' }}</td>
                                <td>{{ $item->email ?: '-' }}</td>
                                <td>{{ $item->accounts_count ?? 0 }} linked account(s)</td>
                            @else
                                <td>
                                    <p class="table-primary">{{ $item->name }}</p>
                                    @unless($item->is_active)
                                        <p class="table-secondary table-secondary--archived">Archived library value</p>
                                    @endunless
                                </td>
                            @endif

                            <td>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusPillClass((bool) $item->is_active) }}">
                                    {{ $item->is_active ? 'Active' : 'Archived' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <button type="button"
                                            class="table-action table-action--edit"
                                            data-edit-button
                                            data-id="{{ $item->id }}"
                                            data-record='@json($editPayload)'
                                            @disabled(! $item->is_active)>
                                        Edit
                                    </button>

                                    @if($item->is_active)
                                        <form method="POST"
                                              action="{{ route('admin.libraries.archive', ['library' => $definition['key'], 'item' => $item->id]) }}"
                                              class="inline-flex"
                                              onsubmit="return confirm('Archive this {{ strtolower($definition['singular']) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action table-action--archive">Archive</button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.libraries.restore', ['library' => $definition['key'], 'item' => $item->id]) }}"
                                              class="inline-flex"
                                              onsubmit="return confirm('Reactivate this {{ strtolower($definition['singular']) }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="table-action table-action--restore">Reactivate</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $definition['key'] === 'referral-institutions' ? 7 : 6 }}" class="empty-state">
                                No {{ strtolower($definition['title']) }} found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $items->links() }}
        </div>
    </section>
</main>

<div id="createLibraryModal" class="modal-shell hidden">
    <div class="modal-backdrop" data-close-create-modal></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <p class="panel-kicker">Create</p>
                <h2 class="panel-title">Add {{ $definition['singular'] }}</h2>
            </div>
            <button type="button" class="modal-close" data-close-create-modal>Close</button>
        </div>

        <form method="POST" action="{{ $libraryStoreRoutes[$definition['key']] }}" class="modal-form">
            @csrf
            @include('admin.partials.library-form-fields', [
                'definition' => $definition,
                'formOptions' => $formOptions,
                'prefix' => 'create',
                'item' => null,
            ])

            <div>
                <label class="label">Status</label>
                <select name="is_active" class="input">
                    <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                    <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-create-modal>Cancel</button>
                <button type="submit" class="btn-primary">Save {{ $definition['singular'] }}</button>
            </div>
        </form>
    </div>
</div>

<div id="editLibraryModal" class="modal-shell hidden">
    <div class="modal-backdrop" data-close-edit-modal></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <p class="panel-kicker">Update</p>
                <h2 class="panel-title">Edit {{ $definition['singular'] }}</h2>
            </div>
            <button type="button" class="modal-close" data-close-edit-modal>Close</button>
        </div>

        <form method="POST" id="editLibraryForm" action="{{ route('admin.libraries.update', ['library' => $definition['key'], 'item' => 0]) }}" class="modal-form">
            @csrf
            @method('PATCH')
            @include('admin.partials.library-form-fields', [
                'definition' => $definition,
                'formOptions' => $formOptions,
                'prefix' => 'edit',
                'item' => null,
            ])

            <div>
                <label class="label">Status</label>
                <select name="is_active" id="editLibraryStatus" class="input">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-edit-modal>Cancel</button>
                <button type="submit" class="btn-primary">Update {{ $definition['singular'] }}</button>
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
    max-width:760px;
}
.libraries-hero-button{
    display:inline-flex;
    align-items:center;
    gap:8px;
    justify-content:center;
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
.table-secondary{
    margin-top:6px;
    color:#64748b;
    font-size:13px;
}
.table-secondary--archived{
    color:#8b5e3c;
    font-weight:600;
}
.library-row--archived{
    background:linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%);
}
.library-row--archived .table-primary{
    color:#475569;
}
.library-row--archived td{
    border-bottom-color:#e5e7eb;
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
.table-action--restore{
    background:#ecfdf5;
    color:#166534;
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
    width:min(720px, calc(100% - 32px));
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
@media (max-width: 900px){
    .library-filter{
        grid-template-columns:1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const createModal = document.getElementById('createLibraryModal');
    const editModal = document.getElementById('editLibraryModal');
    const openCreateBtn = document.getElementById('openCreateLibraryModalBtn');
    const editButtons = document.querySelectorAll('[data-edit-button]');
    const editForm = document.getElementById('editLibraryForm');

    const toggleModal = (modal, open) => {
        if (!modal) return;
        modal.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
    };

    openCreateBtn?.addEventListener('click', () => toggleModal(createModal, true));
    document.querySelectorAll('[data-close-create-modal]').forEach((element) => {
        element.addEventListener('click', () => toggleModal(createModal, false));
    });
    document.querySelectorAll('[data-close-edit-modal]').forEach((element) => {
        element.addEventListener('click', () => toggleModal(editModal, false));
    });

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            const id = button.dataset.id;

            if (!editForm || !id) {
                return;
            }

            editForm.action = @json(route('admin.libraries.update', ['library' => $definition['key'], 'item' => '__ITEM__'])).replace('__ITEM__', id);

            Object.entries(record).forEach(([key, value]) => {
                if (key === 'categories') {
                    editForm.querySelectorAll('input[name="categories[]"]').forEach((checkbox) => {
                        checkbox.checked = Array.isArray(value) && value.includes(checkbox.value);
                    });
                    return;
                }

                const field = editForm.querySelector(`[name="${key}"]`);
                if (!field) return;

                if (field.tagName === 'TEXTAREA') {
                    field.value = value ?? '';
                    return;
                }

                if (key === 'is_active') {
                    field.value = value ? '1' : '0';
                    return;
                }

                field.value = value ?? '';
            });

            toggleModal(editModal, true);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            toggleModal(createModal, false);
            toggleModal(editModal, false);
        }
    });
});
</script>

@endsection
