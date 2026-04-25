@extends('layouts.app')

@section('content')

@php
$statusOrder = [
    'submitted',
    'under_review',
    'for_approval',
    'approved',
    'released'
];

$currentStatus = $latestApplication->status ?? null;
$currentIndex = in_array($currentStatus, $statusOrder, true)
    ? array_search($currentStatus, $statusOrder, true)
    : -1;
@endphp

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
    {{ session('success') }}
</div>
@endif

<div class="p-8 max-w-7xl mx-auto space-y-10 bg-surface">

<section class="relative rounded-xl overflow-hidden bg-gradient-to-br from-primary to-primary-container p-12 text-white shadow-xl">
    <div>
        <h2 class="text-3xl font-bold">Welcome back.</h2>
        <p class="text-sm mt-2">Ready to proceed with your service requests?</p>

        <a href="/client/application">
            <button class="mt-4 bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                Apply for Assistance
            </button>
        </a>
    </div>
</section>

<section class="space-y-6">

<div class="flex items-center justify-between">
    <div>
        <h3 class="text-2xl font-bold text-sky-950">Active Application Status</h3>
        <p class="text-sm text-on-surface-variant">
            Tracking ID: {{ $latestApplication->reference_no ?? 'N/A' }}
        </p>
    </div>

    <span class="px-4 py-1.5 bg-tertiary-container/10 text-tertiary-fixed-dim rounded-full text-xs font-bold uppercase">
        {{ $latestApplication ? str_replace('_',' ', $latestApplication->status) : 'No Application' }}
    </span>
</div>

<div class="bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">

    @php
        $steps = [
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'for_approval' => 'For Approval',
            'approved' => 'Approved',
            'released' => 'Released'
        ];
    @endphp

    @if($currentStatus === 'cancelled')
        <div class="rounded-2xl border border-slate-300 bg-slate-50 px-6 py-5 text-center">
            <p class="text-sm font-bold uppercase tracking-wide text-slate-700">Application Cancelled</p>
            <p class="mt-2 text-sm text-slate-600">
                This application was cancelled during review because it did not meet the frequency of assistance rules.
            </p>
            @if(!empty($latestApplication?->denial_reason))
                <p class="mt-3 text-sm text-slate-700">
                    Reason: {{ $latestApplication->denial_reason }}
                </p>
            @endif
        </div>
    @else
    <div class="grid grid-cols-5 gap-0 px-6">
        @foreach($steps as $key => $label)
            @php
                $stepIndex = array_search($key, $statusOrder);
                $done = $stepIndex <= $currentIndex;
                $leftDone = $stepIndex > 0 && ($stepIndex - 1) < $currentIndex;
                $rightDone = $stepIndex < count($statusOrder) - 1 && $stepIndex < $currentIndex;
            @endphp

            <div class="relative flex flex-col items-center text-center">
                @if($stepIndex > 0)
                    <div class="absolute top-5 right-1/2 h-1 w-1/2 -translate-y-1/2 {{ $leftDone ? 'bg-[#0B3C5D]' : 'bg-slate-200' }}"></div>
                @endif

                @if($stepIndex < count($statusOrder) - 1)
                    <div class="absolute top-5 left-1/2 h-1 w-1/2 -translate-y-1/2 {{ $rightDone ? 'bg-[#0B3C5D]' : 'bg-slate-200' }}"></div>
                @endif

                <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 {{ $done ? 'bg-[#0B3C5D] text-white' : 'bg-slate-200 text-slate-500' }}">
                    @if($done)
                        &#10003;
                    @else
                        {{ $stepIndex + 1 }}
                    @endif
                </div>

                <p class="mt-3 text-sm font-semibold leading-tight px-1 {{ $done ? 'text-[#0B3C5D]' : 'text-slate-500' }}">
                    {{ $label }}
                </p>
            </div>
        @endforeach
    </div>
    @endif

</div>

</section>

<section class="space-y-6">

<div class="flex items-center justify-between">
    <h3 class="text-2xl font-bold text-sky-950">History & Submissions</h3>

    <div class="flex gap-2">
        <form method="GET" class="flex gap-2">

            <select name="status" class="px-3 py-2 text-sm border rounded-lg">
                <option value="">All Status</option>
                <option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
                <option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Under Review</option>
                <option value="for_approval" {{ request('status')=='for_approval'?'selected':'' }}>For Approval</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="released" {{ request('status')=='released'?'selected':'' }}>Released</option>
                <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
            </select>

            <select name="type" class="px-3 py-2 text-sm border rounded-lg">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="px-4 py-2 text-sm bg-surface-container-high rounded-lg">
                Apply
            </button>

        </form>
    </div>
</div>

<div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/10 overflow-hidden">

<table class="w-full text-left border-collapse">

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

@forelse($applications as $app)

<tr class="hover:bg-surface transition-colors">

<td class="px-6 py-5">
<span class="font-mono text-xs font-bold text-sky-800">
    {{ $app->reference_no }}
</span>
</td>

<td class="px-6 py-5">
<p class="text-sm font-semibold text-on-surface">
    {{ $app->assistanceType->name ?? 'N/A' }}
</p>
<p class="text-xs text-on-surface-variant">
    {{ $app->assistanceSubtype->name ?? '' }}
</p>
</td>

<td class="px-6 py-5 text-sm text-on-surface-variant">
    {{ $app->created_at->format('M d, Y') }}
</td>

<td class="px-6 py-5">
<span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase
    @if($app->status == 'submitted') bg-yellow-100 text-yellow-700
    @elseif($app->status == 'under_review') bg-blue-100 text-blue-700
    @elseif($app->status == 'for_approval') bg-primary-fixed text-on-primary-fixed
    @elseif($app->status == 'approved') bg-green-100 text-green-700
    @elseif($app->status == 'released') bg-green-200 text-green-900
    @elseif($app->status == 'cancelled') bg-slate-200 text-slate-700
    @else bg-gray-100 text-gray-700
    @endif
">
    {{ str_replace('_',' ', $app->status) }}
</span>
</td>

<td class="px-6 py-5">
<a href="{{ route('client.application.show', $app->id) }}"
   class="text-primary font-bold hover:underline text-sm">
    View Details
</a>
</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center py-6 text-gray-500">
    No applications found.
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

<div class="mt-6 px-2">
    {{ $applications->links() }}
</div>

</section>

</div>

@endsection
