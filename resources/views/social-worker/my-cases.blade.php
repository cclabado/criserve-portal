@extends('layouts.app')

@section('content')

<main class="p-8 space-y-6 max-w-6xl mx-auto">

<header class="rounded-2xl bg-gradient-to-br from-[#234E70] to-[#2f6a91] p-8 text-white shadow">
    <p class="text-xs uppercase tracking-[0.2em] opacity-80">Social Worker Module</p>
    <h1 class="text-3xl font-bold mt-3">My Catered / Assessed Applications</h1>
    <p class="text-sm opacity-80 mt-2 max-w-2xl">
        Review all applications that you personally assessed or moved forward in the workflow.
    </p>
</header>

<form method="GET" action="{{ route('socialworker.my-cases') }}"
      class="bg-white rounded-xl shadow p-4 flex justify-between items-center flex-wrap gap-4">
    <div class="flex gap-4 flex-wrap">
    <div>
        <p class="text-xs text-gray-500 mb-1">STATUS FILTER</p>
        <select name="status" class="input">
            <option value="all">All Statuses</option>
            <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
            <option value="under_review" @selected(request('status') === 'under_review')>Under Review</option>
            <option value="for_approval" @selected(request('status') === 'for_approval')>For Approval</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            <option value="denied" @selected(request('status') === 'denied')>Denied</option>
            <option value="released" @selected(request('status') === 'released')>Released</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
    </div>

    <div>
        <p class="text-xs text-gray-500 mb-1">APPLICATION SEARCH</p>
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Reference no., client, or beneficiary"
               class="input min-w-[280px]">
    </div>

    <div>
        <p class="text-xs text-gray-500 mb-1">DATE FROM</p>
        <input type="date"
               name="date_from"
               value="{{ request('date_from') }}"
               class="input">
    </div>

    <div>
        <p class="text-xs text-gray-500 mb-1">DATE TO</p>
        <input type="date"
               name="date_to"
               value="{{ request('date_to') }}"
               class="input">
    </div>
    </div>

    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('socialworker.my-cases') }}"
           class="px-4 py-2 bg-gray-200 rounded-lg text-sm">
            Clear
        </a>
        <button type="submit"
                class="px-4 py-2 bg-[#0B3C5D] text-white rounded-lg text-sm">
            Filter
        </button>
        <button type="submit"
                name="export"
                value="xlsx"
                class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-lg text-sm font-semibold">
            Export Excel
        </button>
    </div>
</form>

<section class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full min-w-[980px] text-left">
        <thead class="bg-gray-100 text-xs uppercase text-gray-500">
            <tr>
                <th class="px-6 py-4">Applicant Identity</th>
                <th class="px-6 py-4">Service Category</th>
                <th class="px-6 py-4">Current Status</th>
                <th class="px-6 py-4">Submission Date</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>

        <tbody class="divide-y">
            @forelse($applications as $app)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-[#0B3C5D]">
                                {{ strtoupper(substr($app->client->first_name, 0, 1)) }}
                            </div>

                            <div>
                                <p class="font-semibold text-sm">
                                    {{ $app->client->first_name }} {{ $app->client->last_name }}
                                </p>
                                @if($app->beneficiary && strcasecmp($app->beneficiary->relationshipData?->name ?? '', 'Self') !== 0)
                                    <p class="text-xs text-slate-600">
                                        Beneficiary:
                                        {{ trim(collect([
                                            $app->beneficiary->first_name,
                                            $app->beneficiary->middle_name,
                                            $app->beneficiary->last_name,
                                            $app->beneficiary->extension_name,
                                        ])->filter()->implode(' ')) }}
                                        @if($app->beneficiary->relationshipData?->name)
                                            ({{ $app->beneficiary->relationshipData->name }})
                                        @endif
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500">
                                    ID: {{ $app->reference_no }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-sm">
                        <div class="font-medium text-slate-800">
                            {{ $app->assistanceType->name ?? 'N/A' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $app->assistanceSubtype->name ?? 'Subtype not set' }}
                        </div>
                        @if($app->frequency_status && $app->frequency_status !== 'review_required')
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase
                                    @if($app->frequency_status === 'eligible') bg-emerald-100 text-emerald-800
                                    @elseif($app->frequency_status === 'blocked') bg-rose-100 text-rose-800
                                    @elseif($app->frequency_status === 'overridden') bg-sky-100 text-sky-800
                                    @else bg-slate-100 text-slate-700
                                    @endif">
                                    {{ str_replace('_', ' ', $app->frequency_status) }}
                                </span>
                            </div>
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        @php $status = $app->status; @endphp

                        <span class="px-3 py-1 text-xs font-bold rounded-full
                            @if($status == 'submitted') bg-yellow-100 text-yellow-700
                            @elseif($status == 'under_review') bg-blue-100 text-blue-700
                            @elseif($status == 'approved') bg-green-100 text-green-700
                            @elseif($status == 'denied') bg-red-100 text-red-700
                            @elseif($status == 'released') bg-emerald-100 text-emerald-700
                            @elseif($status == 'cancelled') bg-slate-200 text-slate-700
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ strtoupper(str_replace('_', ' ', $status)) }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $app->created_at->format('M d, Y') }}
                    </td>

                    <td class="px-6 py-4 text-sm">
                        <div class="flex justify-end items-center h-full">
                            <a href="{{ route('socialworker.show', $app->id) }}"
                               class="group relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 shadow-sm transition"
                               aria-label="View application details">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span class="pointer-events-none absolute -top-11 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow transition-opacity duration-150 group-hover:opacity-100">
                                    View details
                                </span>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                        No handled applications found yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="px-6 py-4 border-t">
        {{ $applications->links() }}
    </div>
</section>

</main>

@endsection
