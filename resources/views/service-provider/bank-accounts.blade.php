@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.45),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">Service Provider Workspace</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Bank Accounts</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Add multiple receiving accounts for {{ $provider->name }} and set one default account so new SOA submissions use it automatically.
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
            <p class="font-semibold">Please review the bank account details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Add Account</p>
            <h2 class="mt-2 text-2xl font-black text-sky-950">Enroll Bank Account</h2>

            <form method="POST" action="{{ route('service-provider.bank-accounts.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label">Bank Name</label>
                    <select name="bank_id" class="input">
                        <option value="">Select bank</option>
                        @foreach($bankOptions as $bankOption)
                            <option value="{{ $bankOption->id }}" @selected((string) old('bank_id') === (string) $bankOption->id)>
                                {{ $bankOption->name }}{{ $bankOption->category ? ' • '.$bankOption->category : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_id')
                        <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Account Name</label>
                    <input type="text" name="account_name" value="{{ old('account_name') }}" class="input" placeholder="Registered account name">
                </div>
                <div>
                    <label class="label">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" class="input" placeholder="Enter account number">
                </div>
                <div>
                    <label class="label">Branch Name</label>
                    <input type="text" name="branch_name" value="{{ old('branch_name') }}" class="input" placeholder="Optional branch name">
                </div>
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default')) class="mt-1 rounded border-slate-300 text-[#234E70] focus:ring-[#234E70]">
                    <span>
                        <span class="block font-semibold text-slate-900">Set as default transfer account</span>
                        <span class="mt-1 block text-xs text-slate-500">This account will be preselected on future SOA submissions.</span>
                    </span>
                </label>
                <button type="submit" class="btn-primary">Add Bank Account</button>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Saved Accounts</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">Receiving Accounts</h2>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    {{ $provider->bankAccounts->where('is_active', true)->count() }} account{{ $provider->bankAccounts->where('is_active', true)->count() === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($provider->bankAccounts->where('is_active', true) as $bankAccount)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($bankAccount->is_default)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">
                                            Default
                                        </span>
                                    @endif
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Transfer Account</span>
                                </div>
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $bankAccount->resolvedBankName() }}</h3>
                                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $bankAccount->account_name }}</p>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                                    <span>Type: {{ $bankAccount->bank?->category ?: 'Bank' }}</span>
                                    <span>Account No.: {{ $bankAccount->maskedAccountNumber() }}</span>
                                    <span>Branch: {{ $bankAccount->branch_name ?: 'Not specified' }}</span>
                                </div>
                            </div>

                            @if(! $bankAccount->is_default)
                                <form method="POST" action="{{ route('service-provider.bank-accounts.default', $bankAccount->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                        Set as Default
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No bank accounts added yet.
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</main>

@endsection
