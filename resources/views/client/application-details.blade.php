@extends('layouts.app')

@section('content')

@php
    $frequencyBadgeClasses = [
        'eligible' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'review_required' => 'bg-amber-100 text-amber-800 border border-amber-200',
        'blocked' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'overridden' => 'bg-sky-100 text-sky-800 border border-sky-200',
        'not_applicable' => 'bg-slate-100 text-slate-700 border border-slate-200',
    ];
@endphp

<div class="max-w-7xl mx-auto py-8 space-y-8">

<!-- HEADER -->
<div class="mb-6">
    <a href="/client/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
        ← BACK TO DASHBOARD
    </a>

    <h1 class="text-3xl font-bold text-[#234E70]">
        Application Details
    </h1>

    <p class="text-gray-500">
        Reference No: {{ $application->reference_no }}
    </p>
</div>

<!-- ================= CLIENT ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

<div class="mb-5">
    <h2 class="text-lg font-bold text-[#234E70] flex items-center gap-2">
        Client Information
    </h2>
    <div class="w-14 h-1 bg-[#234E70] rounded mt-1"></div>
</div>

<div class="grid grid-cols-4 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Last Name</p>
<p class="font-semibold text-gray-800">{{ $application->client->last_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">First Name</p>
<p class="font-semibold text-gray-800">{{ $application->client->first_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Middle Name</p>
<p class="font-semibold text-gray-800">{{ $application->client->middle_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Extension</p>
<p class="font-semibold text-gray-800">{{ $application->client->extension_name }}</p>
</div>

</div>

<div class="grid grid-cols-2 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Address</p>
<p class="font-semibold text-gray-800">{{ $application->client->full_address }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Contact Number</p>
<p class="font-semibold text-gray-800">{{ $application->client->contact_number }}</p>
</div>

</div>

<div class="grid grid-cols-3 gap-6">

<div>
<p class="text-xs text-gray-500">Sex</p>
<p class="font-semibold text-gray-800">{{ $application->client->sex }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Birthdate</p>
<p class="font-semibold text-gray-800">{{ $application->client->birthdate }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Civil Status</p>
<p class="font-semibold text-gray-800">{{ $application->client->civil_status }}</p>
</div>

</div>

</div>

<!-- ================= BENEFICIARY ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

<div class="mb-5">
    <h2 class="text-lg font-bold text-[#234E70]">Beneficiary Information</h2>
    <div class="w-14 h-1 bg-[#234E70] rounded mt-1"></div>
</div>

<div class="grid grid-cols-4 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Last Name</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->last_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">First Name</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->first_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Middle Name</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->middle_name }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Extension</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->extension_name }}</p>
</div>

</div>

<div class="grid grid-cols-3 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Sex</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->sex }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Birthdate</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->birthdate }}</p>
</div>

<div>
<p class="text-xs text-gray-500">Contact Number</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->contact_number }}</p>
</div>

</div>

<div>
<p class="text-xs text-gray-500">Full Address</p>
<p class="font-semibold text-gray-800">{{ $application->beneficiary->full_address }}</p>
</div>

</div>

<!-- ================= FAMILY ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

<div class="mb-5">
    <h2 class="text-lg font-bold text-[#234E70]">Family Composition</h2>
    <div class="w-14 h-1 bg-[#234E70] rounded mt-1"></div>
</div>

<div class="space-y-3">

@forelse($application->familyMembers as $fam)
<div class="bg-gray-50 rounded-xl px-5 py-4 grid grid-cols-3 gap-4 border border-gray-100">

<div>
<p class="text-xs text-gray-500">Full Name</p>
<p class="font-semibold text-gray-800">
    {{ $fam->last_name }}, {{ $fam->first_name }}
</p>
</div>

<div>
<p class="text-xs text-gray-500">Relationship</p>
<p class="font-semibold text-gray-800">
    {{ $fam->relationship }}
</p>
</div>

<div>
<p class="text-xs text-gray-500">Birthdate</p>
<p class="font-semibold text-gray-800">
    {{ $fam->birthdate }}
</p>
</div>

</div>
@empty
<p class="text-gray-500">No family records</p>
@endforelse

</div>

</div>

<!-- ================= ASSISTANCE ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

<div class="mb-5">
    <h2 class="text-lg font-bold text-[#234E70]">Assistance Information</h2>
    <div class="w-14 h-1 bg-[#234E70] rounded mt-1"></div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Type</p>
<p class="font-semibold text-gray-800">
    {{ $application->assistanceType->name ?? '' }}
</p>
</div>

<div>
<p class="text-xs text-gray-500">Subtype</p>
<p class="font-semibold text-gray-800">
    {{ $application->assistanceSubtype->name ?? '' }}
</p>
</div>

</div>

<div class="mb-6">
<p class="text-xs text-gray-500">Mode of Assistance</p>
<p class="font-semibold text-gray-800">
    {{ $application->modeOfAssistance->name ?? $application->mode_of_assistance }}
</p>
</div>

<div>
<p class="text-xs text-gray-500 mb-2">Documents</p>

@forelse($application->documents as $doc)
<a href="{{ route('documents.show', $doc->id) }}"
   class="block text-blue-600 hover:underline text-sm">
    {{ $doc->file_name }}
</a>
@empty
<p class="text-gray-500">No documents uploaded</p>
@endforelse

</div>

</div>

<!-- ================= ASSESSMENT ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

<div class="mb-5">
    <h2 class="text-lg font-bold text-[#234E70]">Initial Assessment Details</h2>
    <div class="w-14 h-1 bg-[#234E70] rounded mt-1"></div>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">

<div>
<p class="text-xs text-gray-500">Schedule</p>
<p class="font-semibold text-gray-800">
    {{ $application->schedule_date ? \Carbon\Carbon::parse($application->schedule_date)->format('M d, Y h:i A') : 'Not scheduled yet' }}
</p>
</div>

<div>
<p class="text-xs text-gray-500">Meeting Link</p>
@if($application->meeting_link)
    <a href="{{ $application->meeting_link }}"
       target="_blank"
       class="font-semibold text-blue-600 hover:underline break-all">
        {{ $application->meeting_link }}
    </a>
@else
    <p class="font-semibold text-gray-800">No meeting link yet</p>
@endif
</div>

<div>
<p class="text-xs text-gray-500">Assistance Detail</p>
<p class="font-semibold text-gray-800">
    {{ $application->assistanceDetail->name ?? '-' }}
</p>
</div>

</div>

<div>
<p class="text-xs text-gray-500">Assessment Note</p>
<p class="font-semibold text-gray-800 whitespace-pre-line">
    {{ $application->notes ?: 'No assessment notes yet.' }}
</p>
</div>

</div>

</div>

@endsection
