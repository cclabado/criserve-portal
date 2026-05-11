@extends('layouts.app')

@section('content')

@php
    $formUser = $formUser ?? auth()->user();
    $existingClient = $client ?? null;
    $prefillUser = ($useAccountPrefill ?? true) ? $formUser : null;
    $backUrl = $backUrl ?? '/client/dashboard';
    $formAction = $formAction ?? '/client/application';
    $pageTitle = $pageTitle ?? 'New Assistance Application';
    $pageSubtitle = $pageSubtitle ?? 'Assistance to Individuals in Crisis Situation';
    $lookupUrl = $lookupUrl ?? route('client.beneficiary-profile.lookup');
    $relationships = \App\Models\Relationship::where('is_active', true)->where('name', '!=', 'Other')->get();
    $familyRelationships = $relationships->where('name', '!=', 'Self')->values();
    $assistanceTypes = \App\Models\AssistanceType::with([
        'subtypes' => fn ($query) => $query->where('is_active', true)->with([
            'frequencyRule',
            'documentRequirements',
            'details' => fn ($detailQuery) => $detailQuery->where('is_active', true)->with([
                'frequencyRule',
                'documentRequirements',
            ]),
        ]),
    ])->where('is_active', true)->get();
    $modesOfAssistance = \App\Models\ModeOfAssistance::where('is_active', true)->orderBy('name')->get();
    $serviceProviders = \App\Models\ServiceProvider::where('is_active', true)->orderBy('name')->get();
    $assistanceDetailsBySubtype = [];
    $serviceProviderDirectory = $serviceProviders->map(fn ($provider) => [
        'id' => (string) $provider->id,
        'name' => $provider->name,
        'categories' => $provider->categories ?? [],
    ])->values();

    foreach ($assistanceTypes as $type) {
        foreach ($type->subtypes as $subtype) {
            $assistanceDetailsBySubtype[(string) $subtype->id] = $subtype->details->map(fn ($detail) => [
                'id' => (string) $detail->id,
                'name' => $detail->name,
                'documentRequirements' => $detail->documentRequirements->map(fn ($requirement) => [
                    'id' => (string) $requirement->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'is_required' => (bool) $requirement->is_required,
                    'applies_when_amount_exceeds' => $requirement->applies_when_amount_exceeds !== null ? (float) $requirement->applies_when_amount_exceeds : null,
                ])->values()->all(),
                'frequencyRule' => $detail->frequencyRule ? [
                    'id' => (string) $detail->frequencyRule->id,
                    'requires_reference_date' => (bool) $detail->frequencyRule->requires_reference_date,
                    'requires_case_key' => (bool) $detail->frequencyRule->requires_case_key,
                    'allows_exception_request' => (bool) $detail->frequencyRule->allows_exception_request,
                    'notes' => $detail->frequencyRule->notes,
                ] : null,
            ])->values()->all();
        }
    }

    $clientFamily = ($existingClient?->familyMembers ?? collect())
        ->whereNull('beneficiary_profile_id')
        ->values()
        ->map(function ($member) {
            return [
                'id' => $member->id,
                'last_name' => $member->last_name,
                'first_name' => $member->first_name,
                'middle_name' => $member->middle_name,
                'extension_name' => $member->extension_name,
                'relationship' => $member->relationship,
                'birthdate' => $member->birthdate,
            ];
        })->values();

    $oldFamily = old('family_last_name')
        ? collect(old('family_last_name'))->map(function ($lastName, $index) {
            return [
                'id' => old("family_id.$index"),
                'last_name' => $lastName,
                'first_name' => old("family_first_name.$index"),
                'middle_name' => old("family_middle_name.$index"),
                'extension_name' => old("family_extension_name.$index"),
                'relationship' => old("family_relationship.$index"),
                'birthdate' => old("family_birthdate.$index"),
            ];
        })->values()
        : null;
@endphp

<div class="max-w-7xl mx-auto py-8"
     x-data="applicationForm()"
     x-init="init()">

<div class="mb-6">
    <a href="{{ $backUrl }}" class="text-sm text-gray-600 mb-2 inline-block">
        &larr; Back to Dashboard
    </a>

    <h1 class="text-3xl font-bold text-[#234E70]">
        {{ $pageTitle }}
    </h1>

    <p class="text-gray-500">
        {{ $pageSubtitle }}
    </p>
</div>

@if ($errors->any())
<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
    <p class="font-semibold">Please complete the required fields before proceeding.</p>
    <ul class="mt-2 list-disc pl-5">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="flex gap-4 mb-8">
    <template x-for="(label, index) in ['Personal Info', 'Beneficiary Info', 'Family Composition', 'Assistance & Document']" :key="index">
        <div @click="goToStep(index + 1)"
             :class="step == index + 1 ? 'bg-[#234E70] text-white' : 'bg-gray-100 text-gray-500'"
             class="px-6 py-4 rounded-xl w-64 cursor-pointer transition">
            <p class="text-xs" x-text="'STEP ' + (index + 1)"></p>
            <p class="font-semibold" x-text="label"></p>
        </div>
    </template>
</div>

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" @submit="return validateStep(4)">
@csrf

<datalist id="relationship-options">
    <template x-for="option in relationshipOptions" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="family-relationship-options">
    <template x-for="option in familyRelationshipOptions" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="assistance-type-options">
    <template x-for="option in assistanceTypeOptions" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="assistance-subtype-options">
    <template x-for="option in filteredSubtypes()" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="assistance-detail-options">
    <template x-for="option in currentDetails()" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="mode-options">
    <template x-for="option in modeOptions" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<datalist id="service-provider-options">
    <template x-for="option in filteredServiceProviders()" :key="option.id">
        <option :value="option.name"></option>
    </template>
</datalist>

<div x-show="step == 1">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Client Information</h2>

<div class="grid grid-cols-4 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Last Name</label>
<input x-ref="last_name" name="last_name" value="{{ old('last_name', $existingClient->last_name ?? $prefillUser?->last_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">First Name</label>
<input x-ref="first_name" name="first_name" value="{{ old('first_name', $existingClient->first_name ?? $prefillUser?->first_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Middle Name</label>
<input x-ref="middle_name" name="middle_name" value="{{ old('middle_name', $existingClient->middle_name ?? $prefillUser?->middle_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Extension</label>
<input x-ref="extension_name" name="extension_name" value="{{ old('extension_name', $existingClient->extension_name ?? $prefillUser?->extension_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Address</label>
<input x-ref="full_address" name="full_address" value="{{ old('full_address', $existingClient->full_address ?? $prefillUser?->full_address ?? $prefillUser?->address ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Contact Number</label>
<input x-ref="contact_number" name="contact_number" value="{{ old('contact_number', $existingClient->contact_number ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
</div>

<div class="grid grid-cols-3 gap-4">
<div>
<label class="text-xs text-gray-500">Sex</label>
<div class="select-shell">
<select x-ref="sex" name="sex" class="form-select">
<option value="">Select</option>
<option value="Male" {{ old('sex', $existingClient->sex ?? $prefillUser?->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
<option value="Female" {{ old('sex', $existingClient->sex ?? $prefillUser?->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
</select>
</div>
</div>
<div>
<label class="text-xs text-gray-500">Birthdate</label>
<input x-ref="birthdate" type="date" name="birthdate" value="{{ old('birthdate', $existingClient->birthdate ?? $prefillUser?->birthdate ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Civil Status</label>
<div class="select-shell">
<select name="civil_status" class="form-select">
<option value="">Select</option>
<option value="Single" {{ old('civil_status', $existingClient->civil_status ?? $prefillUser?->civil_status ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
<option value="Married" {{ old('civil_status', $existingClient->civil_status ?? $prefillUser?->civil_status ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
<option value="Widowed" {{ old('civil_status', $existingClient->civil_status ?? $prefillUser?->civil_status ?? '') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
<option value="Separated" {{ old('civil_status', $existingClient->civil_status ?? $prefillUser?->civil_status ?? '') == 'Separated' ? 'selected' : '' }}>Separated</option>
</select>
</div>
</div>
</div>

<div class="mt-4 relative" @click.away="closeDropdown('relationship')">
<label class="text-xs text-gray-500">Relationship to Beneficiary</label>
<input type="hidden" name="relationship_id" x-model="relationship">
<input x-model="relationshipSearch"
       @focus="openDropdown('relationship')"
       @click="openDropdown('relationship')"
       @input="handleSearchInput('relationship', 'relationshipSearch', relationshipOptions)"
       placeholder="Type to search relationship"
       class="border p-2 rounded-lg w-full">
<div x-show="isDropdownOpen('relationship')" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
    <template x-for="option in filteredNamedOptions(relationshipOptions, relationshipSearch)" :key="option.id">
        <button type="button"
                @click="selectDropdownOption('relationship', 'relationshipSearch', option, 'handleRelationshipChange')"
                class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
            <span x-text="option.name"></span>
        </button>
    </template>
    <div x-show="filteredNamedOptions(relationshipOptions, relationshipSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
        No matching relationship found.
    </div>
</div>
</div>

<div class="text-right mt-6">
<button type="button" @click="continueFromStep1()" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">
Continue &rarr;
</button>
</div>

<p x-show="errors.step1" x-text="errors.step1" class="mt-4 text-sm font-medium text-red-600"></p>

</div>
</div>

<div x-show="step == 2">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Beneficiary Information</h2>

<div class="grid grid-cols-4 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Last Name</label>
<input x-ref="bene_last_name" name="bene_last_name" :value="relationship == '1' ? $refs.last_name.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">First Name</label>
<input x-ref="bene_first_name" name="bene_first_name" :value="relationship == '1' ? $refs.first_name.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Middle Name</label>
<input x-ref="bene_middle_name" name="bene_middle_name" :value="relationship == '1' ? $refs.middle_name.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Extension</label>
<input x-ref="bene_extension_name" name="bene_extension_name" :value="relationship == '1' ? $refs.extension_name.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
</div>

<div class="grid grid-cols-3 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Sex</label>
<div class="select-shell">
<select x-ref="bene_sex" name="bene_sex" x-bind:value="relationship == '1' ? $refs.sex.value : ''" :disabled="relationship == '1'" class="form-select">
<option value="">Select</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
</select>
</div>
</div>
<div>
<label class="text-xs text-gray-500">Birthdate</label>
<input x-ref="bene_birthdate" type="date" name="bene_birthdate" :value="relationship == '1' ? $refs.birthdate.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Contact Number</label>
<input x-ref="bene_contact_number" name="bene_contact_number" :value="relationship == '1' ? $refs.contact_number.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>
</div>

<div>
<label class="text-xs text-gray-500">Address</label>
<input x-ref="bene_full_address" name="bene_full_address" :value="relationship == '1' ? $refs.full_address.value : ''" :readonly="relationship == '1'" class="border p-2 rounded-lg w-full">
</div>

<div class="flex justify-between mt-6">
<button type="button" @click="step = 1" class="bg-gray-200 px-6 py-3 rounded-xl">&larr; Back</button>
<button type="button" @click="goToFamilyStep()" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">Continue &rarr;</button>
</div>

<p x-show="errors.step2" x-text="errors.step2" class="mt-4 text-sm font-medium text-red-600"></p>

</div>
</div>

<div x-show="step == 3">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<div class="flex justify-between items-center mb-4">
<h2 class="font-bold">Step 3: Family Composition</h2>
<button type="button" @click="addRow()" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-xl">
+ Add Family Member
</button>
</div>

<p class="text-sm text-gray-500 mb-2" x-text="relationship == '1'
    ? 'This is the saved family composition for the client account. Updates here will carry into future self applications.'
    : 'This family composition belongs to the beneficiary profile. If this beneficiary was used before, their saved household is loaded here.'"></p>

<template x-if="loadedProfileName">
    <p class="text-sm text-emerald-700 mb-4">
        Loaded saved family composition for <span class="font-semibold" x-text="loadedProfileName"></span>.
    </p>
</template>

<div class="grid gap-4 px-4 mb-2 text-xs text-gray-500 md:grid-cols-[1.5fr_1fr_0.9fr_0.45fr]">
<div>FULL NAME</div>
<div>RELATIONSHIP</div>
<div>DATE OF BIRTH</div>
<div class="text-center">ACTIONS</div>
</div>

<div id="family-table" class="space-y-3">
    <template x-for="(member, index) in familyRows" :key="index">
        <div class="grid items-center gap-4 rounded-xl bg-gray-100 px-4 py-3 family-row md:grid-cols-[1.5fr_1fr_0.9fr_0.45fr]">
            <input type="hidden" name="family_id[]" :value="member.id ?? ''">

            <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-4">
                <input name="family_last_name[]" placeholder="Last" x-model="member.last_name" class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white">
                <input name="family_first_name[]" placeholder="First" x-model="member.first_name" class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white">
                <input name="family_middle_name[]" placeholder="Middle" x-model="member.middle_name" class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white">
                <input name="family_extension_name[]" placeholder="Ext." x-model="member.extension_name" class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white">
            </div>

            <div class="relative" @click.away="closeFamilyRelationshipDropdown(index)">
                <input type="hidden" name="family_relationship[]" x-model="member.relationship">
                <input x-model="member.relationship_search"
                       @focus="openFamilyRelationshipDropdown(index)"
                       @click="openFamilyRelationshipDropdown(index)"
                       @input="handleFamilyRelationshipInput(member, index)"
                       placeholder="Type to search relationship"
                       class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white w-full">
                <div x-show="isFamilyRelationshipDropdownOpen(index)" x-transition class="absolute z-20 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    <template x-for="option in filteredNamedOptions(familyRelationshipOptions, member.relationship_search)" :key="option.id">
                        <button type="button"
                                @click="selectFamilyRelationshipOption(index, option)"
                                class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                            <span x-text="option.name"></span>
                        </button>
                    </template>
                    <div x-show="filteredNamedOptions(familyRelationshipOptions, member.relationship_search).length === 0" class="px-4 py-3 text-sm text-slate-400">
                        No matching relationship found.
                    </div>
                </div>
            </div>

            <input type="date" name="family_birthdate[]" x-model="member.birthdate" class="min-w-0 rounded-lg border border-transparent bg-white/70 px-3 py-2 outline-none transition focus:border-[#234E70] focus:bg-white">

            <div class="text-center md:text-right">
                <button type="button" @click="removeRow(index)" class="text-red-500">Remove</button>
            </div>
        </div>
    </template>
</div>

<div class="flex justify-between mt-6">
<button type="button" @click="step = relationship == '1' ? 1 : 2" class="bg-gray-200 px-6 py-3 rounded-xl">&larr; Back</button>
<button type="button" @click="continueFromStep3()" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">Continue &rarr;</button>
</div>

<p x-show="errors.step3" x-text="errors.step3" class="mt-4 text-sm font-medium text-red-600"></p>

</div>
</div>

<div x-show="step == 4">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Assistance Information</h2>

<div class="grid gap-4 md:grid-cols-2">
    <div class="relative" @click.away="closeDropdown('assistance_type')">
        <label class="text-xs font-semibold tracking-wide text-slate-500">Type of Assistance</label>
        <input type="hidden" name="assistance_type_id" x-model="selectedType">
        <input x-model="selectedTypeSearch"
               @focus="openDropdown('assistance_type')"
               @click="openDropdown('assistance_type')"
               @input="handleSearchInput('selectedType', 'selectedTypeSearch', assistanceTypeOptions)"
               placeholder="Type to search assistance type"
               class="mt-1 border p-2 rounded-lg w-full">
        <div x-show="isDropdownOpen('assistance_type')" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
            <template x-for="option in filteredNamedOptions(assistanceTypeOptions, selectedTypeSearch)" :key="option.id">
                <button type="button"
                        @click="selectDropdownOption('selectedType', 'selectedTypeSearch', option, 'handleTypeChange')"
                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                    <span x-text="option.name"></span>
                </button>
            </template>
            <div x-show="filteredNamedOptions(assistanceTypeOptions, selectedTypeSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
                No matching assistance type found.
            </div>
        </div>
    </div>

    <div class="relative" @click.away="closeDropdown('assistance_subtype')">
        <label class="text-xs font-semibold tracking-wide text-slate-500">Specific Assistance</label>
        <input type="hidden" name="assistance_subtype_id" x-model="selectedSubtype">
        <input x-model="selectedSubtypeSearch"
               @focus="openDropdown('assistance_subtype')"
               @click="openDropdown('assistance_subtype')"
               @input="handleSearchInput('selectedSubtype', 'selectedSubtypeSearch', filteredSubtypes())"
               :disabled="filteredSubtypes().length === 0"
               :placeholder="selectedType ? 'Type to search specific assistance' : 'Choose a type first'"
               class="mt-1 border p-2 rounded-lg w-full">
        <div x-show="isDropdownOpen('assistance_subtype') && filteredSubtypes().length > 0" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
            <template x-for="option in filteredNamedOptions(filteredSubtypes(), selectedSubtypeSearch)" :key="option.id">
                <button type="button"
                        @click="selectDropdownOption('selectedSubtype', 'selectedSubtypeSearch', option, 'handleSubtypeChange')"
                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                    <span x-text="option.name"></span>
                </button>
            </template>
            <div x-show="filteredNamedOptions(filteredSubtypes(), selectedSubtypeSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
                No matching assistance found.
            </div>
        </div>
    </div>

    <div x-show="selectedSubtype" x-cloak class="relative" @click.away="closeDropdown('assistance_detail')">
        <label class="text-xs font-semibold tracking-wide text-slate-500">Assistance Detail</label>
        <template x-if="selectedSubtypeHasDetails()">
            <div>
                <input type="hidden" name="assistance_detail_id" x-model="selectedDetail">
                <input x-model="selectedDetailSearch"
                       @focus="openDropdown('assistance_detail')"
                       @click="openDropdown('assistance_detail')"
                       @input="handleSearchInput('selectedDetail', 'selectedDetailSearch', currentDetails())"
                       placeholder="Type to search assistance detail"
                       class="mt-1 border p-2 rounded-lg w-full">
                <div x-show="isDropdownOpen('assistance_detail')" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    <template x-for="option in filteredNamedOptions(currentDetails(), selectedDetailSearch)" :key="option.id">
                        <button type="button"
                                @click="selectDropdownOption('selectedDetail', 'selectedDetailSearch', option, 'syncSelectedServiceProvider')"
                                class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                            <span x-text="option.name"></span>
                        </button>
                    </template>
                    <div x-show="filteredNamedOptions(currentDetails(), selectedDetailSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
                        No matching detail found.
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500" x-show="currentDetails().length > 0">
                    Select the category that best matches the request before uploading documents.
                </p>
            </div>
        </template>
        <template x-if="!selectedSubtypeHasDetails()">
            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                No assistance detail is required for this assistance.
            </div>
        </template>
    </div>

    <div class="relative" @click.away="closeDropdown('mode')">
        <label class="text-xs font-semibold tracking-wide text-slate-500">Mode of Assistance</label>
        <input type="hidden" name="mode_of_assistance_id" x-model="selectedMode">
        <input x-model="selectedModeSearch"
               @focus="openDropdown('mode')"
               @click="openDropdown('mode')"
               @input="handleSearchInput('selectedMode', 'selectedModeSearch', modeOptions)"
               placeholder="Type to search mode of assistance"
               class="mt-1 border p-2 rounded-lg w-full">
        <div x-show="isDropdownOpen('mode')" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
            <template x-for="option in filteredNamedOptions(modeOptions, selectedModeSearch)" :key="option.id">
                <button type="button"
                        @click="selectDropdownOption('selectedMode', 'selectedModeSearch', option, 'syncSelectedServiceProvider')"
                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                    <span x-text="option.name"></span>
                </button>
            </template>
            <div x-show="filteredNamedOptions(modeOptions, selectedModeSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
                No matching mode found.
            </div>
        </div>
    </div>

    <div x-show="isGuaranteeLetterSelection()" x-cloak class="relative" @click.away="closeDropdown('service_provider')">
        <label class="text-xs font-semibold tracking-wide text-slate-500">Service Provider</label>
        <input type="hidden" name="service_provider_id" x-model="selectedServiceProvider">
        <input x-model="selectedServiceProviderSearch"
               @focus="openDropdown('service_provider')"
               @click="openDropdown('service_provider')"
               @input="handleSearchInput('selectedServiceProvider', 'selectedServiceProviderSearch', filteredServiceProviders())"
               placeholder="Type to search service provider"
               class="mt-1 border p-2 rounded-lg w-full">
        <div x-show="isDropdownOpen('service_provider')" x-transition class="absolute z-30 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
            <template x-for="option in filteredNamedOptions(filteredServiceProviders(), selectedServiceProviderSearch)" :key="option.id">
                <button type="button"
                        @click="selectDropdownOption('selectedServiceProvider', 'selectedServiceProviderSearch', option)"
                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                    <span x-text="option.name"></span>
                </button>
            </template>
            <div x-show="filteredNamedOptions(filteredServiceProviders(), selectedServiceProviderSearch).length === 0" class="px-4 py-3 text-sm text-slate-400">
                No matching service provider found.
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-500" x-text="serviceProviderHelperText()"></p>
    </div>
</div>

<div class="mt-5" x-show="isMedicalAssistanceSelection()">
    <label class="text-xs font-semibold tracking-wide text-slate-500">Amount Needed / Requested</label>
    <input x-ref="amount_needed" x-model="amountNeeded" type="number" min="0" step="0.01" name="amount_needed" value="{{ old('amount_needed') }}" class="mt-1 border p-2 rounded-lg w-full" placeholder="0.00">
    <p class="mt-2 text-xs text-slate-500">This amount is used for medical document rules that only apply when the request exceeds P10,000.00.</p>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5" x-show="hasSpecificRequirements()">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="font-semibold text-slate-900">Required Documents</h3>
            <p class="text-sm text-slate-600">Upload the matching files for the assistance you selected.</p>
        </div>
        <span class="inline-flex w-fit rounded-full border border-sky-200 bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800" x-text="currentDocumentRequirements().length + ' checklist item(s)'"></span>
    </div>

    <div class="mt-4 space-y-4">
        <template x-for="requirement in currentDocumentRequirements()" :key="requirement.id">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="font-semibold text-slate-900" x-text="requirement.name"></p>
                        <p class="mt-1 text-sm text-slate-600" x-text="requirement.description || 'Upload the file that satisfies this requirement.'"></p>
                    </div>
                    <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold"
                          :class="requirementBadgeClass(requirement)"
                          x-text="requirementStatusLabel(requirement)"></span>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold tracking-wide text-slate-500">Upload File(s)</label>
                    <input :name="'required_documents[' + requirement.id + '][]'" type="file" multiple class="mt-1 border p-2 rounded-lg w-full">
                </div>
            </div>
        </template>
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" x-show="!hasSpecificRequirements()">
    <label class="text-xs font-semibold tracking-wide text-slate-500">Upload Supporting Documents</label>
    <input x-ref="generic_documents" type="file" name="documents[]" multiple class="mt-1 border p-2 rounded-lg w-full">
    <p class="mt-2 text-xs text-slate-500">At least one supporting document is required when no specific checklist is configured yet.</p>
</div>

<div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
    <label class="text-xs font-semibold tracking-wide text-slate-500" x-text="hasSpecificRequirements() ? 'Additional Supporting Documents (Optional)' : 'Additional Supporting Documents'"></label>
    <input type="file" name="documents[]" multiple class="mt-1 border p-2 rounded-lg w-full">
    <p class="mt-2 text-xs text-slate-500">Upload any extra files that can help support the application.</p>
</div>

<div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" x-show="showsCrisisInterventionReminders()">
    <h3 class="font-semibold">Important Reminders</h3>
    <ul class="mt-3 list-disc space-y-2 pl-5">
        <li>All financial assistance from DSWD Crisis Intervention Division is fully received by beneficiaries.</li>
        <li>No fixer policy: No employee or non-employee is authorized to collect any fee.</li>
        <li>Submission of fake or falsified documents is punishable and will be subject to proper legal action.</li>
    </ul>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <label class="text-xs font-semibold tracking-wide text-slate-500">Client Signature</label>
            <p class="mt-2 text-sm text-slate-600">Please sign inside the pad below before submitting the application.</p>
        </div>
        <button type="button" @click="clearSignaturePad()" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Clear Signature
        </button>
    </div>

    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3">
        <canvas x-ref="signature_canvas"
                @mousedown="startSignature($event)"
                @mousemove="drawSignature($event)"
                @mouseup="endSignature($event)"
                @mouseleave="endSignature($event)"
                @touchstart="startSignature($event)"
                @touchmove="drawSignature($event)"
                @touchend="endSignature($event)"
                class="block w-full rounded-xl bg-white touch-none"></canvas>
    </div>

    <input type="hidden" name="client_signature_data" x-ref="client_signature_data" :value="clientSignatureData">
</div>

<div class="flex justify-between mt-6">
<button type="button" @click="step = 3" class="bg-gray-200 px-6 py-3 rounded-xl">&larr; Back</button>
<button type="submit" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">Submit</button>
</div>

<p x-show="errors.step4" x-text="errors.step4" class="mt-4 text-sm font-medium text-red-600"></p>

</div>
</div>

</form>

</div>

<style>
[x-cloak]{
    display:none !important;
}
.select-shell{
    position:relative;
}
.select-shell::after{
    content:'';
    position:absolute;
    right:14px;
    top:50%;
    width:10px;
    height:10px;
    border-right:2px solid #64748b;
    border-bottom:2px solid #64748b;
    transform:translateY(-70%) rotate(45deg);
    pointer-events:none;
}
.form-select{
    width:100%;
    appearance:none;
    border:1px solid #cbd5e1;
    border-radius:0.9rem;
    background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding:0.8rem 2.9rem 0.8rem 0.95rem;
    font-size:0.95rem;
    color:#0f172a;
    transition:border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
}
.form-select:focus{
    outline:none;
    border-color:#234E70;
    box-shadow:0 0 0 4px rgba(35,78,112,.12);
    background:#fff;
}
.form-select:disabled{
    color:#94a3b8;
    background:#f8fafc;
    cursor:not-allowed;
}
</style>

<script>
function applicationForm() {
    return {
        step: 1,
        relationshipOptions: @js($relationships->map(fn ($rel) => ['id' => (string) $rel->id, 'name' => $rel->name])->values()),
        familyRelationshipOptions: @js($familyRelationships->map(fn ($rel) => ['id' => (string) $rel->id, 'name' => $rel->name])->values()),
        assistanceTypesData: @js($assistanceTypes->map(fn ($type) => [
            'id' => (string) $type->id,
            'name' => $type->name,
            'subtypes' => $type->subtypes->map(fn ($subtype) => [
                'id' => (string) $subtype->id,
                'name' => $subtype->name,
                'documentRequirements' => $subtype->documentRequirements->map(fn ($requirement) => [
                    'id' => (string) $requirement->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'is_required' => (bool) $requirement->is_required,
                    'applies_when_amount_exceeds' => $requirement->applies_when_amount_exceeds !== null ? (float) $requirement->applies_when_amount_exceeds : null,
                ])->values()->all(),
                'frequencyRule' => $subtype->frequencyRule ? [
                    'id' => (string) $subtype->frequencyRule->id,
                    'requires_reference_date' => (bool) $subtype->frequencyRule->requires_reference_date,
                    'requires_case_key' => (bool) $subtype->frequencyRule->requires_case_key,
                    'allows_exception_request' => (bool) $subtype->frequencyRule->allows_exception_request,
                    'notes' => $subtype->frequencyRule->notes,
                ] : null,
                'details' => $subtype->details->map(fn ($detail) => [
                    'id' => (string) $detail->id,
                    'name' => $detail->name,
                    'documentRequirements' => $detail->documentRequirements->map(fn ($requirement) => [
                        'id' => (string) $requirement->id,
                        'name' => $requirement->name,
                        'description' => $requirement->description,
                        'is_required' => (bool) $requirement->is_required,
                        'applies_when_amount_exceeds' => $requirement->applies_when_amount_exceeds !== null ? (float) $requirement->applies_when_amount_exceeds : null,
                    ])->values()->all(),
                    'frequencyRule' => $detail->frequencyRule ? [
                        'id' => (string) $detail->frequencyRule->id,
                        'requires_reference_date' => (bool) $detail->frequencyRule->requires_reference_date,
                        'requires_case_key' => (bool) $detail->frequencyRule->requires_case_key,
                        'allows_exception_request' => (bool) $detail->frequencyRule->allows_exception_request,
                        'notes' => $detail->frequencyRule->notes,
                    ] : null,
                ])->values()->all(),
            ])->values()->all(),
        ])->values()->all()),
        detailOptionsBySubtype: @js($assistanceDetailsBySubtype),
        modeOptions: @js($modesOfAssistance->map(fn ($mode) => ['id' => (string) $mode->id, 'name' => $mode->name])->values()),
        selectedType: @js((string) old('assistance_type_id', '')),
        selectedTypeSearch: '',
        selectedSubtype: @js((string) old('assistance_subtype_id', '')),
        selectedSubtypeSearch: '',
        selectedDetail: @js((string) old('assistance_detail_id', '')),
        selectedDetailSearch: '',
        selectedMode: @js((string) old('mode_of_assistance_id', '')),
        selectedModeSearch: '',
        selectedServiceProvider: @js((string) old('service_provider_id', '')),
        selectedServiceProviderSearch: '',
        amountNeeded: @js((string) old('amount_needed', '')),
        serviceProviders: @js($serviceProviderDirectory),
        relationship: @js((string) old('relationship_id', '')),
        relationshipSearch: '',
        clientFamily: @js($clientFamily),
        familyRows: @js($oldFamily ?? $clientFamily->values()),
        clientSignatureData: @js(old('client_signature_data', '')),
        loadedProfileName: '',
        lookupUrl: @js($lookupUrl),
        csrfToken: @js(csrf_token()),
        signaturePadContext: null,
        signaturePadDrawing: false,
        activeDropdown: '',
        familyRelationshipOpenIndex: null,
        errors: {
            step1: '',
            step2: '',
            step3: '',
            step4: '',
        },

        init() {
            if (!Array.isArray(this.familyRows) || this.familyRows.length === 0) {
                this.familyRows = [this.emptyFamilyRow()];
            }

            this.syncSearchLabels();
            this.handleTypeChange(false);
            this.handleSubtypeChange(false);
            this.$nextTick(() => this.initSignaturePad());
        },

        syncSearchLabels() {
            this.relationshipSearch = this.optionNameById(this.relationshipOptions, this.relationship);
            this.selectedTypeSearch = this.optionNameById(this.assistanceTypeOptions, this.selectedType);
            this.selectedSubtypeSearch = this.optionNameById(this.filteredSubtypes(), this.selectedSubtype);
            this.selectedDetailSearch = this.optionNameById(this.currentDetails(), this.selectedDetail);
            this.selectedModeSearch = this.optionNameById(this.modeOptions, this.selectedMode);
            this.selectedServiceProviderSearch = this.optionNameById(this.filteredServiceProviders(), this.selectedServiceProvider);
            this.familyRows = this.cloneRows(this.familyRows);
        },

        optionNameById(options, id) {
            return options.find((option) => option.id === String(id || ''))?.name || '';
        },

        openDropdown(name) {
            this.activeDropdown = name;
        },

        closeDropdown(name) {
            if (this.activeDropdown === name) {
                this.activeDropdown = '';
            }
        },

        isDropdownOpen(name) {
            return this.activeDropdown === name;
        },

        filteredNamedOptions(options, query) {
            const needle = String(query || '').trim().toLowerCase();

            if (needle === '') {
                return options;
            }

            return options.filter((option) => String(option.name || '').toLowerCase().includes(needle));
        },

        handleSearchInput(field, searchField, options) {
            this.openDropdown(this.dropdownNameForField(field));

            if (String(this[searchField] || '').trim() === '') {
                this[field] = '';

                if (field === 'relationship') {
                    this.handleRelationshipChange();
                } else if (field === 'selectedType') {
                    this.handleTypeChange();
                } else if (field === 'selectedSubtype') {
                    this.handleSubtypeChange();
                } else if (field === 'selectedDetail' || field === 'selectedMode' || field === 'selectedServiceProvider') {
                    if (field !== 'selectedServiceProvider') {
                        this[field] = '';
                    }
                    this.syncSelectedServiceProvider();
                }

                return;
            }

            this.syncSearchSelection(field, this[searchField], options);
        },

        dropdownNameForField(field) {
            return {
                relationship: 'relationship',
                selectedType: 'assistance_type',
                selectedSubtype: 'assistance_subtype',
                selectedDetail: 'assistance_detail',
                selectedMode: 'mode',
                selectedServiceProvider: 'service_provider',
            }[field] || '';
        },

        selectDropdownOption(field, searchField, option, callback = null) {
            this[field] = option.id;
            this[searchField] = option.name;
            this.closeDropdown(this.dropdownNameForField(field));

            if (callback && typeof this[callback] === 'function') {
                this[callback]();
            }
        },

        syncSearchSelection(field, label, options) {
            const matched = options.find((option) => option.name.toLowerCase() === String(label || '').trim().toLowerCase());
            this[field] = matched?.id || '';

            if (!matched && String(label || '').trim() === '') {
                this[field] = '';
            }
        },

        syncRowSearchSelection(row, label, options) {
            const matched = options.find((option) => option.name.toLowerCase() === String(label || '').trim().toLowerCase());
            row.relationship = matched?.id || '';

            if (!matched && String(label || '').trim() === '') {
                row.relationship = '';
            }
        },

        openFamilyRelationshipDropdown(index) {
            this.familyRelationshipOpenIndex = index;
        },

        closeFamilyRelationshipDropdown(index = null) {
            if (index === null || this.familyRelationshipOpenIndex === index) {
                this.familyRelationshipOpenIndex = null;
            }
        },

        isFamilyRelationshipDropdownOpen(index) {
            return this.familyRelationshipOpenIndex === index;
        },

        handleFamilyRelationshipInput(member, index) {
            this.openFamilyRelationshipDropdown(index);

            if (String(member.relationship_search || '').trim() === '') {
                member.relationship = '';
                return;
            }

            this.syncRowSearchSelection(member, member.relationship_search, this.familyRelationshipOptions);
        },

        selectFamilyRelationshipOption(index, option) {
            const row = this.familyRows[index];

            if (!row) {
                return;
            }

            row.relationship = option.id;
            row.relationship_search = option.name;
            this.closeFamilyRelationshipDropdown(index);
        },

        get assistanceTypeOptions() {
            return this.assistanceTypesData.map((type) => ({ id: type.id, name: type.name }));
        },

        initSignaturePad() {
            const canvas = this.$refs.signature_canvas;

            if (!canvas) {
                return;
            }

            const ratio = window.devicePixelRatio || 1;
            const width = canvas.offsetWidth || 600;
            const height = 220;

            canvas.width = width * ratio;
            canvas.height = height * ratio;
            canvas.style.height = `${height}px`;

            const context = canvas.getContext('2d');
            context.scale(ratio, ratio);
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.strokeStyle = '#163750';
            context.lineWidth = 2.4;
            this.signaturePadContext = context;

            if (this.clientSignatureData) {
                const image = new Image();
                image.onload = () => {
                    context.clearRect(0, 0, width, height);
                    context.drawImage(image, 0, 0, width, height);
                };
                image.src = this.clientSignatureData;
            }
        },

        signaturePoint(event) {
            const canvas = this.$refs.signature_canvas;
            const rect = canvas.getBoundingClientRect();
            const source = event.touches?.[0] || event.changedTouches?.[0] || event;

            return {
                x: source.clientX - rect.left,
                y: source.clientY - rect.top,
            };
        },

        startSignature(event) {
            if (!this.signaturePadContext) {
                return;
            }

            event.preventDefault();
            const point = this.signaturePoint(event);
            this.signaturePadDrawing = true;
            this.signaturePadContext.beginPath();
            this.signaturePadContext.moveTo(point.x, point.y);
        },

        drawSignature(event) {
            if (!this.signaturePadDrawing || !this.signaturePadContext) {
                return;
            }

            event.preventDefault();
            const point = this.signaturePoint(event);
            this.signaturePadContext.lineTo(point.x, point.y);
            this.signaturePadContext.stroke();
            this.syncSignatureField();
        },

        endSignature(event) {
            if (!this.signaturePadDrawing || !this.signaturePadContext) {
                return;
            }

            event.preventDefault();
            this.signaturePadDrawing = false;
            this.syncSignatureField();
        },

        clearSignaturePad() {
            const canvas = this.$refs.signature_canvas;

            if (!canvas || !this.signaturePadContext) {
                return;
            }

            this.signaturePadContext.clearRect(0, 0, canvas.width, canvas.height);
            this.clientSignatureData = '';
            if (this.$refs.client_signature_data) {
                this.$refs.client_signature_data.value = '';
            }
        },

        syncSignatureField() {
            const canvas = this.$refs.signature_canvas;

            if (!canvas) {
                return;
            }

            this.clientSignatureData = canvas.toDataURL('image/png');

            if (this.$refs.client_signature_data) {
                this.$refs.client_signature_data.value = this.clientSignatureData;
            }
        },

        hasClientSignature() {
            return String(this.clientSignatureData || '').startsWith('data:image/png;base64,');
        },

        handleTypeChange(resetSubtype = true) {
            if (resetSubtype) {
                this.selectedSubtype = '';
                this.selectedDetail = '';
                this.selectedSubtypeSearch = '';
                this.selectedDetailSearch = '';
            }

            this.syncSelectedServiceProvider();
            this.selectedTypeSearch = this.optionNameById(this.assistanceTypeOptions, this.selectedType);
            this.selectedSubtypeSearch = this.optionNameById(this.filteredSubtypes(), this.selectedSubtype);
            this.selectedDetailSearch = this.optionNameById(this.currentDetails(), this.selectedDetail);
        },

        handleSubtypeChange(resetDetail = true) {
            if (resetDetail) {
                this.selectedDetail = '';
                this.selectedDetailSearch = '';
            }

            if (!this.selectedSubtypeHasDetails()) {
                this.selectedDetail = '';
                this.selectedDetailSearch = '';
            }

            this.syncSelectedServiceProvider();
            this.selectedSubtypeSearch = this.optionNameById(this.filteredSubtypes(), this.selectedSubtype);
            this.selectedDetailSearch = this.optionNameById(this.currentDetails(), this.selectedDetail);
        },

        currentSubtype() {
            for (const type of this.assistanceTypesData) {
                const subtype = (type.subtypes || []).find((item) => item.id === this.selectedSubtype);
                if (subtype) {
                    return subtype;
                }
            }

            return null;
        },

        currentDetail() {
            return this.currentDetails().find((item) => item.id === this.selectedDetail) || null;
        },

        filteredSubtypes() {
            const type = this.assistanceTypesData.find((item) => item.id === this.selectedType);
            return type?.subtypes || [];
        },

        currentDetails() {
            const mappedDetails = this.detailOptionsBySubtype?.[this.selectedSubtype];

            if (Array.isArray(mappedDetails) && mappedDetails.length > 0) {
                return mappedDetails;
            }

            return this.currentSubtype()?.details || [];
        },

        currentSubtypeName() {
            return this.currentSubtype()?.name || '';
        },

        currentDetailName() {
            return this.currentDetail()?.name || '';
        },

        normalizedCategoryText(value) {
            return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        },

        categoryMatchers() {
            return {
                'Chemo': ['chemo', 'chemotherapy'],
                'Device': ['device', 'assistive device'],
                'Diagnostic Center': ['diagnostic center', 'diagnostic', 'laboratory', 'laboratory request', 'lab'],
                'Dialysis': ['dialysis'],
                'Dialysis Center': ['dialysis center'],
                'Funeral': ['funeral', 'cadaver', 'remains'],
                'Hearing': ['hearing'],
                'Hospital': ['hospital', 'admitted', 'admission'],
                'Implant': ['implant'],
                'Medical Devices': ['medical device', 'assistive device'],
                'Optha': ['optha', 'ophtha', 'eye', 'vision'],
                'Pharmacy': ['pharmacy', 'medicine', 'medicines', 'prescription', 'drug'],
                'Procedure': ['procedure', 'operation', 'surgery'],
                'Prosthesis': ['prosthesis'],
                'Prosthesis/Orthotics': ['prosthesis', 'orthotic', 'orthotics'],
                'Theraphy': ['therapy', 'theraphy', 'rehab', 'rehabilitation'],
            };
        },

        inferredServiceProviderCategories() {
            const sources = [this.currentDetailName(), this.currentSubtypeName()]
                .map((value) => this.normalizedCategoryText(value))
                .filter(Boolean);

            for (const source of sources) {
                const matched = Object.entries(this.categoryMatchers())
                    .filter(([, keywords]) => keywords.some((keyword) => source.includes(keyword)))
                    .map(([category]) => category);

                if (matched.length) {
                    return [...new Set(matched)];
                }
            }

            return [];
        },

        filteredServiceProviders() {
            const inferredCategories = this.inferredServiceProviderCategories();

            if (!inferredCategories.length) {
                return this.serviceProviders;
            }

            const matchedProviders = this.serviceProviders.filter((provider) =>
                (provider.categories || []).some((category) => inferredCategories.includes(category))
            );

            return matchedProviders.length ? matchedProviders : this.serviceProviders;
        },

        currentDocumentRequirements() {
            const subtypeRequirements = this.currentSubtype()?.documentRequirements || [];
            const detailRequirements = this.currentDetail()?.documentRequirements || [];

            return [...subtypeRequirements, ...detailRequirements].filter((requirement) => this.requirementIsActive(requirement));
        },

        hasSpecificRequirements() {
            return this.currentDocumentRequirements().length > 0;
        },

        requirementIsActive(requirement) {
            if (requirement.applies_when_amount_exceeds === null) {
                return true;
            }

            return Number(this.amountNeeded || 0) > Number(requirement.applies_when_amount_exceeds);
        },

        requirementStatusLabel(requirement) {
            if (requirement.applies_when_amount_exceeds !== null) {
                return 'Required above P' + Number(requirement.applies_when_amount_exceeds).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            return requirement.is_required ? 'Required' : 'Optional';
        },

        requirementBadgeClass(requirement) {
            if (requirement.applies_when_amount_exceeds !== null) {
                return 'bg-amber-100 text-amber-800';
            }

            return requirement.is_required ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700';
        },

        showsCrisisInterventionReminders() {
            const subtypeName = (this.currentSubtype()?.name || '').toLowerCase();

            return subtypeName.includes('medical assistance')
                || subtypeName.includes('funeral assistance')
                || subtypeName.includes('transportation assistance')
                || subtypeName.includes('cash relief assistance');
        },

        isMedicalAssistanceSelection() {
            return (this.currentSubtype()?.name || '').toLowerCase().includes('medical assistance');
        },

        isGuaranteeLetterSelection() {
            return this.optionNameById(this.modeOptions, this.selectedMode).trim().toLowerCase() === 'guarantee letter';
        },

        serviceProviderHelperText() {
            const inferredCategories = this.inferredServiceProviderCategories();

            if (!inferredCategories.length) {
                return 'Required when the mode of assistance is Guarantee Letter.';
            }

            const matchedProviders = this.serviceProviders.filter((provider) =>
                (provider.categories || []).some((category) => inferredCategories.includes(category))
            );

            if (!matchedProviders.length) {
                return `Required when the mode of assistance is Guarantee Letter. No provider is tagged under ${inferredCategories.join(', ')} yet, so all active providers are shown.`;
            }

            return `Required when the mode of assistance is Guarantee Letter. Showing providers under: ${inferredCategories.join(', ')}.`;
        },

        syncSelectedServiceProvider() {
            if (this.selectedServiceProvider && !this.filteredServiceProviders().some((provider) => provider.id === this.selectedServiceProvider)) {
                this.selectedServiceProvider = '';
                this.selectedServiceProviderSearch = '';
            }

            this.selectedServiceProviderSearch = this.optionNameById(this.filteredServiceProviders(), this.selectedServiceProvider);
        },

        selectedSubtypeHasDetails() {
            return this.currentDetails().length > 0;
        },

        currentFrequencyRule() {
            const subtype = this.currentSubtype();

            if (!subtype) {
                return null;
            }

            const detail = this.currentDetails().find((item) => item.id === this.selectedDetail);

            return detail?.frequencyRule || subtype.frequencyRule || null;
        },

        frequencyPreviewLabel() {
            const rule = this.currentFrequencyRule();

            if (!rule) {
                return 'No Rule';
            }

            if (rule.allows_exception_request) {
                return 'Needs Review';
            }

            return 'Rule Active';
        },

        frequencyPreviewBadgeClass() {
            const rule = this.currentFrequencyRule();

            if (!rule) {
                return 'bg-slate-100 text-slate-700 border border-slate-200';
            }

            if (rule.allows_exception_request) {
                return 'bg-amber-100 text-amber-800 border border-amber-200';
            }

            return 'bg-sky-100 text-sky-800 border border-sky-200';
        },

        clearError(stepKey) {
            this.errors[stepKey] = '';
        },

        goToStep(targetStep) {
            if (targetStep <= this.step) {
                this.step = targetStep;
                return;
            }

            for (let current = this.step; current < targetStep; current++) {
                if (!this.validateStep(current)) {
                    return;
                }
            }

            this.step = targetStep;
        },

        continueFromStep1() {
            if (!this.validateStep(1)) {
                return;
            }

            if (this.relationship === '1') {
                this.goToFamilyStep();
                return;
            }

            this.step = 2;
        },

        handleRelationshipChange() {
            this.loadedProfileName = '';
            this.clearError('step1');

            if (this.relationship === '1') {
                this.familyRows = this.cloneRows(this.clientFamily);
            } else if (this.relationship !== '') {
                this.familyRows = [this.emptyFamilyRow()];
            }

            this.relationshipSearch = this.optionNameById(this.relationshipOptions, this.relationship);
        },

        async goToFamilyStep() {
            if (this.relationship !== '1' && !this.validateStep(2)) {
                return;
            }

            if (this.relationship === '1') {
                this.familyRows = this.cloneRows(this.clientFamily);
                this.loadedProfileName = '';
                this.step = 3;
                return;
            }

            await this.lookupBeneficiaryFamily();
            this.step = 3;
        },

        continueFromStep3() {
            if (!this.validateStep(3)) {
                return;
            }

            this.step = 4;
        },

        async lookupBeneficiaryFamily() {
            this.loadedProfileName = '';

            const payload = {
                last_name: this.$refs.bene_last_name?.value || '',
                first_name: this.$refs.bene_first_name?.value || '',
                middle_name: this.$refs.bene_middle_name?.value || '',
                extension_name: this.$refs.bene_extension_name?.value || '',
                birthdate: this.$refs.bene_birthdate?.value || '',
            };

            if (!payload.last_name || !payload.first_name || !payload.birthdate) {
                this.familyRows = [this.emptyFamilyRow()];
                return;
            }

            if (!this.lookupUrl) {
                this.familyRows = [this.emptyFamilyRow()];
                return;
            }

            const response = await fetch(this.lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (data.profile && Array.isArray(data.family) && data.family.length > 0) {
                this.loadedProfileName = data.profile.name || '';
                this.familyRows = this.cloneRows(data.family);
                return;
            }

            this.familyRows = [this.emptyFamilyRow()];
        },

        addRow() {
            this.clearError('step3');
            this.familyRows.push(this.emptyFamilyRow());
        },

        removeRow(index) {
            if (this.familyRows.length === 1) {
                this.familyRows = [this.emptyFamilyRow()];
                return;
            }

            this.familyRows.splice(index, 1);
        },

        emptyFamilyRow() {
            return {
                id: '',
                last_name: '',
                first_name: '',
                middle_name: '',
                extension_name: '',
                relationship: '',
                relationship_search: '',
                birthdate: '',
            };
        },

        cloneRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                return [this.emptyFamilyRow()];
            }

            return rows.map((row) => ({
                id: row.id ?? '',
                last_name: row.last_name ?? '',
                first_name: row.first_name ?? '',
                middle_name: row.middle_name ?? '',
                extension_name: row.extension_name ?? '',
                relationship: row.relationship ? String(row.relationship) : '',
                relationship_search: this.optionNameById(this.familyRelationshipOptions, row.relationship),
                birthdate: row.birthdate ?? '',
            }));
        },

        validateStep(stepNumber) {
            if (stepNumber === 1) {
                const valid = [
                    this.$refs.last_name?.value,
                    this.$refs.first_name?.value,
                    this.$refs.full_address?.value,
                    this.$refs.contact_number?.value,
                    this.$refs.sex?.value,
                    this.$refs.birthdate?.value,
                    this.relationship,
                    document.querySelector('select[name="civil_status"]')?.value,
                ].every((value) => String(value || '').trim() !== '');

                this.errors.step1 = valid ? '' : 'Complete all client information fields and select the relationship to beneficiary.';
                return valid;
            }

            if (stepNumber === 2) {
                if (this.relationship === '1') {
                    this.errors.step2 = '';
                    return true;
                }

                const valid = [
                    this.$refs.bene_last_name?.value,
                    this.$refs.bene_first_name?.value,
                    this.$refs.bene_sex?.value,
                    this.$refs.bene_birthdate?.value,
                    this.$refs.bene_contact_number?.value,
                    this.$refs.bene_full_address?.value,
                ].every((value) => String(value || '').trim() !== '');

                this.errors.step2 = valid ? '' : 'Complete the beneficiary details before continuing.';
                return valid;
            }

            if (stepNumber === 3) {
                const validRows = this.familyRows.filter((row) =>
                    [row.last_name, row.first_name, row.relationship, row.birthdate].every((value) => String(value || '').trim() !== '')
                );

                const valid = validRows.length > 0 && validRows.length === this.familyRows.length;
                this.errors.step3 = valid ? '' : 'Add at least one complete family member entry with full name, relationship, and birthdate.';
                return valid;
            }

            if (stepNumber === 4) {
                const assistanceType = this.selectedType;
                const assistanceSubtype = this.selectedSubtype;
                const assistanceDetail = this.selectedDetail;
                const mode = document.querySelector('select[name="mode_of_assistance_id"]')?.value;
                const serviceProvider = this.selectedServiceProvider;
                const amountNeeded = this.$refs.amount_needed?.value;
                const documents = Array.from(document.querySelectorAll('input[name="documents[]"]'))
                    .reduce((count, input) => count + (input.files?.length ?? 0), 0);

                const needsDetail = this.selectedSubtypeHasDetails();
                const requiresAmount = this.isMedicalAssistanceSelection();
                const requiresServiceProvider = this.isGuaranteeLetterSelection();
                const requirementInputsValid = this.hasSpecificRequirements()
                    ? this.currentDocumentRequirements().every((requirement) => {
                        if (!requirement.is_required) {
                            return true;
                        }

                        const input = document.querySelector('input[name="required_documents[' + requirement.id + '][]"]');
                        return (input?.files?.length ?? 0) > 0;
                    })
                    : documents > 0;
                const valid = [assistanceType, assistanceSubtype, mode].every((value) => String(value || '').trim() !== '')
                    && (!requiresAmount || String(amountNeeded || '').trim() !== '')
                    && (!requiresServiceProvider || String(serviceProvider || '').trim() !== '')
                    && (!needsDetail || String(assistanceDetail || '').trim() !== '')
                    && requirementInputsValid
                    && this.hasClientSignature();

                if (valid && requiresServiceProvider && !this.filteredServiceProviders().some((provider) => provider.id === serviceProvider)) {
                    this.errors.step4 = 'The selected service provider does not match the current assistance category.';
                    return false;
                }

                this.errors.step4 = valid ? '' : (requiresAmount
                    ? 'Complete the assistance fields, enter the amount needed, upload the required document checklist, and provide the client signature before submitting.'
                    : 'Complete the assistance fields, upload the required document checklist, and provide the client signature before submitting.');
                return valid;
            }

            return true;
        },
    };
}
</script>

@endsection
