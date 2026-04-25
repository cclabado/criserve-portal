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
      class="bg-white rounded-2xl shadow p-5 grid gap-4 md:grid-cols-[1.3fr,.7fr,auto] items-end">
    <div>
        <label class="label">Search case or applicant</label>
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Reference no. or client name"
               class="input">
    </div>

    <div>
        <label class="label">Status</label>
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

    <div class="flex gap-2">
        <a href="{{ route('socialworker.my-cases') }}" class="btn-secondary text-center">Reset</a>
        <button type="submit" class="btn-primary">Filter</button>
    </div>
</form>

<section class="bg-white rounded-2xl shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-slate-100 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-6 py-4">Applicant</th>
                <th class="px-6 py-4">Reference</th>
                <th class="px-6 py-4">Assistance</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Updated</th>
                <th class="px-6 py-4 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($applications as $app)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-sm text-slate-900">
                            {{ $app->client->first_name }} {{ $app->client->last_name }}
                        </p>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $app->reference_no }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $app->assistanceType->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs font-bold rounded-full
                            @if($app->status === 'submitted') bg-yellow-100 text-yellow-700
                            @elseif($app->status === 'under_review') bg-blue-100 text-blue-700
                            @elseif($app->status === 'for_approval') bg-indigo-100 text-indigo-700
                            @elseif($app->status === 'approved') bg-green-100 text-green-700
                            @elseif($app->status === 'released') bg-emerald-100 text-emerald-700
                            @elseif($app->status === 'denied') bg-red-100 text-red-700
                            @elseif($app->status === 'cancelled') bg-slate-200 text-slate-700
                            @else bg-slate-100 text-slate-600
                            @endif">
                            {{ strtoupper(str_replace('_', ' ', $app->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">{{ $app->updated_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('socialworker.show', $app->id) }}"
                           class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                        No handled applications found yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-6 py-4 border-t">
        {{ $applications->links() }}
    </div>
</section>

</main>

@endsection
