@extends('layouts.app')

@section('content')

<main x-data="userManagement({
        users: @js($users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'service_provider_id' => $user->service_provider_id,
            'referral_institution_id' => $user->referral_institution_id,
            'position_id' => $user->position_id,
            'license_number' => $user->license_number,
            'position_name' => $user->position?->name,
            'approval_min_amount' => $user->approval_min_amount,
            'approval_max_amount' => $user->approval_max_amount,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'extension_name' => $user->extension_name,
            'birthdate' => $user->birthdate,
            'sex' => $user->sex,
            'civil_status' => $user->civil_status,
        ])->values()),
        createBaseUrl: @js(route('admin.users.store')),
        updateBaseUrl: @js(url('/admin/users')),
        serviceProviders: @js($serviceProviders->map(fn ($provider) => [
            'id' => $provider->id,
            'name' => $provider->name,
        ])->values()),
        referralInstitutions: @js($referralInstitutions->map(fn ($institution) => [
            'id' => $institution->id,
            'name' => $institution->name,
        ])->values()),
        positions: @js($positions->map(fn ($position) => [
            'id' => $position->id,
            'name' => $position->name,
            'salary_grade' => $position->salary_grade,
            'requires_license_number' => (bool) $position->requires_license_number,
        ])->values()),
    })"
    class="space-y-6">

    <section class="users-hero">
        <div>
            <p class="users-kicker">Administrator</p>
            <h1 class="users-title">User Management</h1>
            <p class="users-copy">
                Filter all accounts, review who has access, and edit user details through a single update modal.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="users-back">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Dashboard
        </a>
    </section>

    @if(session('success'))
        <div class="users-alert users-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="users-alert users-alert--error">
            <p class="font-semibold">Please review the submitted details.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-3">
        <article class="mini-metric">
            <p class="mini-metric__label">Visible Users</p>
            <p class="mini-metric__value">{{ number_format($users->count()) }}</p>
        </article>

        <article class="mini-metric">
            <p class="mini-metric__label">Active Filter</p>
            <p class="mini-metric__value text-[18px]">
                {{ $filters['role'] === 'all' ? 'All Roles' : ucwords(str_replace('_', ' ', $filters['role'])) }}
            </p>
        </article>

        <article class="mini-metric">
            <p class="mini-metric__label">Search</p>
            <p class="mini-metric__value text-[18px]">
                {{ $filters['search'] !== '' ? $filters['search'] : 'No search filter' }}
            </p>
        </article>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <p class="panel-kicker">Filters</p>
                <h2 class="panel-title">Find Users Quickly</h2>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.users') }}" class="filter-grid mt-6">
            <div>
                <label class="label">Search name or email</label>
                <input type="text"
                       name="search"
                       class="input"
                       value="{{ $filters['search'] }}"
                       placeholder="Search user">
            </div>

            <div>
                <label class="label">Role</label>
                <select name="role" class="input">
                    <option value="all" @selected($filters['role'] === 'all')>All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>
                            {{ ucwords(str_replace('_', ' ', $role)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="{{ route('admin.users') }}" class="btn-secondary text-center">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <p class="panel-kicker">Users</p>
                <h2 class="panel-title">All Registered Accounts</h2>
            </div>

            <button type="button" class="btn-primary" @click="openCreate()">
                Create Account
            </button>
        </div>

        <div class="table-wrap mt-6">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Birthdate</th>
                        <th>Sex</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <p class="table-name">{{ $user->name ?: 'Unnamed User' }}</p>
                                <p class="table-subtitle">{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'No profile name' }}</p>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="user-role-pill">{{ ucwords(str_replace('_', ' ', $user->role)) }}</span></td>
                            <td>{{ $user->birthdate ?: '-' }}</td>
                            <td>{{ $user->sex ?: '-' }}</td>
                            <td>
                                <button type="button"
                                        class="edit-button"
                                        @click="openEdit({{ $user->id }})">
                                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500">No users matched the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-cloak
         x-show="showModal"
         x-transition.opacity
         class="modal-backdrop"
         @click.self="closeModal()"
         @keydown.escape.window="closeModal()">
        <div class="modal-panel">
            <div class="modal-head">
                <div>
                    <p class="panel-kicker" x-text="isCreateMode ? 'Create User' : 'Edit User'"></p>
                    <h2 class="panel-title" x-text="isCreateMode ? 'Create Account' : (form.name || 'Update Account')"></h2>
                    <p class="modal-copy" x-text="isCreateMode ? 'Create a new user account and assign the right role access.' : 'Update profile details and role access for this account.'"></p>
                </div>

                <button type="button" class="modal-close" @click="closeModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="POST" :action="formAction" class="mt-6 space-y-4">
                @csrf
                <template x-if="!isCreateMode">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">First Name</label>
                        <input type="text" name="first_name" class="input" x-model="form.first_name" required>
                    </div>

                    <div>
                        <label class="label">Middle Name</label>
                        <input type="text" name="middle_name" class="input" x-model="form.middle_name">
                    </div>

                    <div>
                        <label class="label">Last Name</label>
                        <input type="text" name="last_name" class="input" x-model="form.last_name" required>
                    </div>

                    <div>
                        <label class="label">Extension</label>
                        <input type="text" name="extension_name" class="input" x-model="form.extension_name">
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Email</label>
                        <input type="email" name="email" class="input" x-model="form.email" required>
                    </div>

                    <div x-show="isCreateMode" x-cloak>
                        <label class="label">Password</label>
                        <input type="password" name="password" class="input" :required="isCreateMode" x-model="form.password">
                    </div>

                    <div x-show="isCreateMode" x-cloak>
                        <label class="label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="input" :required="isCreateMode" x-model="form.password_confirmation">
                    </div>

                    <div>
                        <label class="label">Birthdate</label>
                        <input type="date" name="birthdate" class="input" x-model="form.birthdate">
                    </div>

                    <div>
                        <label class="label">Role</label>
                        <select name="role" class="input" x-model="form.role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="form.role === 'service_provider'" x-cloak class="md:col-span-2">
                        <label class="label">Linked Service Provider</label>
                        <select name="service_provider_id" class="input" x-model="form.service_provider_id">
                            <option value="">Select service provider</option>
                            @foreach($serviceProviders as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="form.role === 'referral_institution'" x-cloak class="md:col-span-2">
                        <label class="label">Linked Referral Institution</label>
                        <select name="referral_institution_id" class="input" x-model="form.referral_institution_id">
                            <option value="">Select referral institution</option>
                            @foreach($referralInstitutions as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="isStaffRole" x-cloak class="md:col-span-2">
                        <label class="label">Position</label>
                        <select name="position_id" class="input" x-model="form.position_id">
                            <option value="">Select position</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">
                                    {{ $position->name }}
                                    @if($position->salary_grade)
                                        (SG {{ $position->salary_grade }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="requiresLicense" x-cloak class="md:col-span-2">
                        <label class="label">License Number</label>
                        <input type="text" name="license_number" class="input" x-model="form.license_number" placeholder="Enter PRC or license number">
                    </div>

                    <div x-show="form.role === 'approving_officer'" x-cloak>
                        <label class="label">Minimum Approval Amount</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="approval_min_amount"
                               class="input"
                               x-model="form.approval_min_amount"
                               placeholder="0.00">
                    </div>

                    <div x-show="form.role === 'approving_officer'" x-cloak>
                        <label class="label">Maximum Approval Amount</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="approval_max_amount"
                               class="input"
                               x-model="form.approval_max_amount"
                               placeholder="Leave blank for no upper limit">
                        <p class="mt-2 text-xs text-slate-500">Leave blank to allow approvals from the minimum amount and up.</p>
                    </div>

                    <div>
                        <label class="label">Sex</label>
                        <select name="sex" class="input" x-model="form.sex">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Civil Status</label>
                        <select name="civil_status" class="input" x-model="form.civil_status">
                            <option value="">Select</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" @click="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" x-text="isCreateMode ? 'Create Account' : 'Save Changes'"></button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
function userManagement(config) {
    return {
        users: config.users,
        createBaseUrl: config.createBaseUrl,
        updateBaseUrl: config.updateBaseUrl,
        serviceProviders: config.serviceProviders,
        referralInstitutions: config.referralInstitutions,
        positions: config.positions,
        showModal: false,
        isCreateMode: false,
        formAction: '',
        form: {
            id: null,
            name: '',
            first_name: '',
            middle_name: '',
            last_name: '',
            extension_name: '',
            email: '',
            birthdate: '',
            sex: '',
            civil_status: '',
            role: 'client',
            service_provider_id: '',
            referral_institution_id: '',
            position_id: '',
            license_number: '',
            approval_min_amount: '',
            approval_max_amount: '',
            password: '',
            password_confirmation: '',
        },
        get isStaffRole() {
            return ['social_worker', 'approving_officer', 'referral_officer'].includes(this.form.role);
        },
        get requiresLicense() {
            const position = this.positions.find((item) => String(item.id) === String(this.form.position_id));

            return Boolean(position?.requires_license_number);
        },
        defaultForm() {
            return {
                id: null,
                name: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                extension_name: '',
                email: '',
                birthdate: '',
                sex: '',
                civil_status: '',
                role: 'client',
                service_provider_id: '',
                referral_institution_id: '',
                position_id: '',
                license_number: '',
                approval_min_amount: '',
                approval_max_amount: '',
                password: '',
                password_confirmation: '',
            };
        },
        openCreate() {
            this.isCreateMode = true;
            this.form = this.defaultForm();
            this.formAction = this.createBaseUrl;
            this.showModal = true;
        },
        openEdit(userId) {
            const user = this.users.find((item) => item.id === userId);

            if (!user) {
                return;
            }

            this.isCreateMode = false;
            this.form = {
                ...this.defaultForm(),
                ...user,
                middle_name: user.middle_name ?? '',
                extension_name: user.extension_name ?? '',
                birthdate: user.birthdate ?? '',
                sex: user.sex ?? '',
                civil_status: user.civil_status ?? '',
                role: user.role ?? 'client',
                service_provider_id: user.service_provider_id ? String(user.service_provider_id) : '',
                referral_institution_id: user.referral_institution_id ? String(user.referral_institution_id) : '',
                position_id: user.position_id ? String(user.position_id) : '',
                license_number: user.license_number ?? '',
                approval_min_amount: user.approval_min_amount ?? '',
                approval_max_amount: user.approval_max_amount ?? '',
            };
            this.formAction = `${this.updateBaseUrl}/${user.id}`;
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
            this.isCreateMode = false;
            this.form = this.defaultForm();
        },
    };
}
</script>

<style>
[x-cloak]{
    display:none !important;
}
.users-hero{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:16px;
    padding:28px 30px;
    border-radius:24px;
    background:
        radial-gradient(circle at top left, rgba(184, 220, 244, .55), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #edf5fb 100%);
    border:1px solid #d9e6f0;
}
.users-kicker,
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.users-title{
    margin-top:10px;
    font-size:34px;
    font-weight:900;
    color:#163750;
}
.users-copy{
    margin-top:10px;
    color:#64748b;
    max-width:760px;
}
.users-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:11px 16px;
    border-radius:14px;
    background:#234E70;
    color:#fff;
    font-weight:700;
}
.users-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.users-alert--success{
    background:#ecfdf5;
    color:#166534;
    border-color:#bbf7d0;
}
.users-alert--error{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}
.mini-metric,
.panel-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.mini-metric__label{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
    font-weight:800;
}
.mini-metric__value{
    margin-top:10px;
    font-size:30px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
}
.panel-title{
    font-size:22px;
    font-weight:900;
    color:#163750;
    margin-top:6px;
}
.filter-grid{
    display:grid;
    gap:16px;
    grid-template-columns:1.4fr 1fr auto;
    align-items:end;
}
.filter-actions{
    display:flex;
    gap:12px;
}
.table-wrap{
    overflow:auto;
}
.admin-table{
    width:100%;
    border-collapse:collapse;
}
.admin-table th,
.admin-table td{
    padding:16px 10px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    font-size:14px;
    vertical-align:middle;
}
.admin-table th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
}
.table-name{
    font-weight:800;
    color:#163750;
}
.table-subtitle{
    margin-top:4px;
    font-size:12px;
    color:#64748b;
}
.user-role-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:8px 12px;
    font-size:12px;
    font-weight:700;
    background:#e6eef5;
    color:#234E70;
}
.edit-button{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 10px;
    border-radius:10px;
    background:#234E70;
    color:#fff;
    font-weight:600;
    font-size:13px;
    line-height:1;
    white-space:nowrap;
}
.modal-backdrop{
    position:fixed;
    inset:0;
    background:rgba(15, 23, 42, .58);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    z-index:60;
}
.modal-panel{
    width:min(920px, 100%);
    max-height:calc(100vh - 48px);
    overflow:auto;
    background:#fff;
    border-radius:24px;
    padding:24px;
    box-shadow:0 24px 60px rgba(15, 23, 42, .28);
}
.modal-head{
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-start;
}
.modal-copy{
    margin-top:8px;
    color:#64748b;
}
.modal-close{
    width:42px;
    height:42px;
    border-radius:999px;
    background:#f1f5f9;
    color:#0f172a;
    display:flex;
    align-items:center;
    justify-content:center;
}
.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    padding-top:8px;
}
@media (max-width: 960px){
    .filter-grid{
        grid-template-columns:1fr;
    }
    .filter-actions{
        flex-direction:column;
    }
    .users-hero{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

@endsection
