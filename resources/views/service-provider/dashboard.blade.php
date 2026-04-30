@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Service Provider</p>
                <h1 class="mt-2 text-3xl font-black text-sky-950">Guarantee Letters</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">
                    Review approved guarantee letters assigned to {{ $provider->name }} and upload the updated statement of account when available.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Assigned Letters</p>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ $applications->count() }}</p>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Pending Statement</p>
                    <p class="mt-3 text-3xl font-black text-amber-900">{{ $pendingStatementCount }}</p>
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
            <p class="font-semibold">Please review the uploaded statement details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Approved Cases</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Assigned Guarantee Letters</h2>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse($applications as $application)
                @php
                    $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $application->reference_no }}</p>
                                <h3 class="mt-2 text-xl font-bold text-slate-900">
                                    {{ $application->client?->first_name }} {{ $application->client?->last_name }}
                                </h3>
                            </div>

                            <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                                <div><span class="font-semibold text-slate-500">Status</span><br>{{ strtoupper(str_replace('_', ' ', $application->status)) }}</div>
                                <div><span class="font-semibold text-slate-500">Assistance</span><br>{{ $application->assistanceSubtype?->name ?? '-' }}</div>
                                <div><span class="font-semibold text-slate-500">Detail</span><br>{{ $application->assistanceDetail?->name ?? '-' }}</div>
                                <div><span class="font-semibold text-slate-500">Amount</span><br>PHP {{ number_format((float) ($application->final_amount ?? $application->recommended_amount ?? 0), 2) }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-800">Current statement status</p>
                                <p class="mt-1">{{ $hasUpdatedStatement ? 'Updated statement of account already uploaded.' : 'Waiting for updated statement of account upload.' }}</p>
                            </div>
                        </div>

                        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Upload Updated Statement</p>
                            <form method="POST"
                                  action="{{ route('service-provider.statement.upload', $application->id) }}"
                                  enctype="multipart/form-data"
                                  class="mt-4 space-y-3">
                                @csrf
                                <input type="file" name="statement_file" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <button type="submit" class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                    Upload Statement
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    No approved guarantee letters are assigned to this service provider account yet.
                </div>
            @endforelse
        </div>
    </section>
</main>

@endsection
