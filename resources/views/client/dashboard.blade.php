@extends('layouts.app')

@section('content')

@php
$statusSteps = [
    'submitted' => 2,        
    'under_review' => 3,
    'for_interview' => 4,
    'approved' => 5,
    'released' => 6,
];

$currentStep = $latestApplication ? ($statusSteps[$latestApplication->status] ?? 1) : 0;
@endphp

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
    {{ session('success') }}
</div>
@endif

<div class="p-8 max-w-7xl mx-auto space-y-10 bg-surface">

<!-- Welcome Section -->
<section class="relative rounded-xl overflow-hidden bg-gradient-to-br from-primary to-primary-container p-12 text-white shadow-xl">
    <div>
        <h2 class="text-3xl font-bold">Welcome back.</h2>
        <p class="text-sm mt-2">Ready to proceed with your service requests?</p>
        <a href="/client/application">
            <button class="mt-4 bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold">
                Apply for Assistance
            </button>
        </a>
    </div>
</section>

<!-- Status Tracker -->
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

<div class="bg-surface-container-lowest p-10 rounded-xl shadow-sm border border-outline-variant/10">

<div class="flex items-center justify-between relative">

<!-- LINE -->
<div class="absolute top-6 left-0 w-full h-1 bg-surface-container-high">
    <div class="bg-primary h-full"
        style="width: {{ ($currentStep / 5) * 100 }}%">
    </div>
</div>

@php
$steps = [
    1 => 'Submitted',
    2 => 'Under Review',
    3 => 'For Interview',
    4 => 'Approved',
    5 => 'Released'
];
@endphp

@foreach($steps as $step => $label)

<div class="flex flex-col items-center gap-2 z-10
    {{ $currentStep < $step ? 'opacity-40' : '' }}">

    <div class="w-12 h-12 rounded-full flex items-center justify-center
        {{ $currentStep >= $step ? 'bg-primary text-white' : 'bg-surface-container-high' }}">

        {{-- COMPLETED --}}
        @if($currentStep > $step)
            ✔

        {{-- CURRENT --}}
        @elseif($currentStep == $step)
            ●

        {{-- UPCOMING --}}
        @else
            ○
        @endif

    </div>

    <p class="text-sm font-bold
        {{ $currentStep >= $step ? 'text-primary' : '' }}">
        {{ $label }}
    </p>

</div>

@endforeach

</div>
</div>

</section>

<!-- Applications Table -->
<section class="space-y-6">

<div class="flex items-center justify-between">
    <h3 class="text-2xl font-bold text-sky-950">History & Submissions</h3>

    <div class="flex gap-2">
        <form method="GET" class="flex gap-2">

            <select name="status" class="px-3 py-2 text-sm border rounded-lg">
                <option value="">All Status</option>
                <option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
                <option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Under Review</option>
                <option value="for_interview" {{ request('status')=='for_interview'?'selected':'' }}>For Interview</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="released" {{ request('status')=='released'?'selected':'' }}>Released</option>
            </select>

            <select name="type" class="px-3 py-2 text-sm border rounded-lg">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}">
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
<th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
Reference ID
</th>
<th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
Type of Assistance
</th>
<th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
Submission Date
</th>
<th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
Current Status
</th>
<th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
Action
</th>
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
    @elseif($app->status == 'for_interview') bg-primary-fixed text-on-primary-fixed
    @elseif($app->status == 'approved') bg-green-100 text-green-700
    @elseif($app->status == 'released') bg-green-200 text-green-900
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

</section>

</div>

@endsection