@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#234E70]">My Approvals</h1>
            <p class="text-gray-500">Applications you opened or decided as an approving officer.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('approving.my-approvals') }}"
          class="rounded-2xl bg-white p-5 shadow">
        <div class="grid gap-4 md:grid-cols-[1.2fr,.8fr,auto] md:items-end">
            <div>
                <label class="block text-sm text-slate-500 mb-2">Search</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Reference no. or applicant name"
                       class="input w-full">
            </div>

            <div>
                <label class="block text-sm text-slate-500 mb-2">Status</label>
                <select name="status" class="input w-full">
                    <option value="all">All Statuses</option>
                    <option value="for_approval" @selected(request('status') === 'for_approval')>For Approval</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="denied" @selected(request('status') === 'denied')>Denied</option>
                    <option value="released" @selected(request('status') === 'released')>Released</option>
                </select>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('approving.my-approvals') }}" class="px-4 py-2 rounded-lg bg-slate-200 text-sm">Reset</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#234E70] text-sm text-white hover:bg-[#18384f]">Filter</button>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-6 py-4">Applicant</th>
                    <th class="px-6 py-4">Assistance Type</th>
                    <th class="px-6 py-4">Final Amount</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-[#234E70]">
                                    {{ strtoupper(substr($app->client->first_name, 0, 1)) }}
                                </div>

                                <div>
                                    <p class="font-semibold text-sm">
                                        {{ $app->client->first_name }} {{ $app->client->last_name }}
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        Ref: {{ $app->reference_no }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $app->assistanceType->name ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm font-semibold text-[#234E70]">
                            PHP {{ number_format($app->final_amount ?? $app->recommended_amount ?? 0, 2) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs rounded-full font-semibold uppercase
                                @if($app->status === 'for_approval') bg-amber-100 text-amber-700
                                @elseif($app->status === 'approved') bg-emerald-100 text-emerald-700
                                @elseif($app->status === 'denied') bg-red-100 text-red-700
                                @elseif($app->status === 'released') bg-sky-100 text-sky-700
                                @else bg-slate-100 text-slate-700
                                @endif">
                                {{ str_replace('_', ' ', $app->status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('approving.show', ['id' => $app->id, 'readonly' => 1]) }}"
                               class="px-4 py-2 bg-[#234E70] text-white rounded-lg text-sm hover:bg-[#18384f]">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            No approvals assigned to you yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $applications->links() }}
        </div>
    </div>

</main>

@endsection
