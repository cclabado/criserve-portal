@extends('layouts.app')

@section('content')

<div class="p-8 max-w-7xl mx-auto space-y-8 bg-surface">

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#123a58] via-[#1d5376] to-[#2b6f95] p-10 text-white shadow-xl">
        <div class="relative z-10 max-w-3xl">
            <span class="inline-flex items-center rounded-full bg-white/15 px-4 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                Client Records
            </span>
            <h1 class="mt-4 text-4xl font-black tracking-tight">My Applications</h1>
            <p class="mt-3 text-sm text-white/85 sm:text-base">
                View all of your submitted requests, monitor their current status, and open each application for full details.
            </p>
        </div>
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute bottom-0 right-24 h-28 w-28 rounded-full bg-cyan-200/20 blur-2xl"></div>
    </section>

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-sky-950">Application List</h2>
                <p class="text-sm text-on-surface-variant">
                    Filter your applications by status or assistance type.
                </p>
            </div>

            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <select name="status" class="min-w-[180px] px-3 py-2 text-sm border rounded-lg">
                    <option value="">All Status</option>
                    <option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
                    <option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Under Review</option>
                    <option value="for_approval" {{ request('status')=='for_approval'?'selected':'' }}>For Approval</option>
                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                    <option value="released" {{ request('status')=='released'?'selected':'' }}>Released</option>
                    <option value="denied" {{ request('status')=='denied'?'selected':'' }}>Denied</option>
                    <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                </select>

                <select name="type" class="min-w-[200px] px-3 py-2 text-sm border rounded-lg">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-[#123a58] rounded-lg hover:bg-[#0f314b] transition">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-outline-variant/10 bg-surface-container-lowest shadow-sm">
            <table class="w-full border-collapse text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Reference ID</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Type of Assistance</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Submission Date</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Current Status</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-surface-container">
                    @forelse($applications as $application)
                        <tr class="hover:bg-surface transition-colors">
                            <td class="px-6 py-5">
                                <span class="font-mono text-xs font-bold text-sky-800">
                                    {{ $application->reference_no }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ $application->assistanceType->name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-on-surface-variant">
                                    {{ $application->assistanceSubtype->name ?? '' }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-sm text-on-surface-variant">
                                {{ $application->created_at->format('M d, Y') }}
                            </td>

                            <td class="px-6 py-5">
                                <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase
                                    @if($application->status == 'submitted') bg-yellow-100 text-yellow-700
                                    @elseif($application->status == 'under_review') bg-blue-100 text-blue-700
                                    @elseif($application->status == 'for_approval') bg-primary-fixed text-on-primary-fixed
                                    @elseif($application->status == 'approved') bg-green-100 text-green-700
                                    @elseif($application->status == 'released') bg-green-200 text-green-900
                                    @elseif($application->status == 'denied') bg-rose-100 text-rose-700
                                    @elseif($application->status == 'cancelled') bg-slate-200 text-slate-700
                                    @else bg-gray-100 text-gray-700
                                    @endif
                                ">
                                    {{ str_replace('_',' ', $application->status) }}
                                </span>
                                @if($application->client_compliance_status === 'requested')
                                    <div class="mt-2">
                                        <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-amber-100 text-amber-800">
                                            For Compliance
                                        </span>
                                    </div>
                                @elseif($application->client_compliance_status === 'resubmitted')
                                    <div class="mt-2">
                                        <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase bg-sky-100 text-sky-800">
                                            Compliance Uploaded
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <a href="{{ route('client.application.show', $application->id) }}"
                                   class="text-primary font-bold hover:underline text-sm">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">
                                No applications found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-2">
            {{ $applications->links() }}
        </div>
    </section>

</div>

@endsection
