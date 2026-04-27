@extends('layouts.app')

@section('content')

@php
    $user = auth()->user();
    $existingClient = $client;
    $relationships = \App\Models\Relationship::all();
    $familyRelationships = $relationships->where('id', '!=', 1)->values();
    $assistanceTypes = \App\Models\AssistanceType::with(['subtypes.frequencyRule', 'subtypes.details.frequencyRule'])->get();
    $modesOfAssistance = \App\Models\ModeOfAssistance::orderBy('name')->get();

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
    <a href="/client/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
        &larr; Back to Dashboard
    </a>

    <h1 class="text-3xl font-bold text-[#234E70]">
        New Assistance Application
    </h1>

    <p class="text-gray-500">
        Assistance to Individuals in Crisis Situation
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

<form method="POST" action="/client/application" enctype="multipart/form-data" @submit="return validateStep(4)">
@csrf

<div x-show="step == 1">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Client Information</h2>

<div class="grid grid-cols-4 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Last Name</label>
<input x-ref="last_name" name="last_name" value="{{ old('last_name', $existingClient->last_name ?? $user->last_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">First Name</label>
<input x-ref="first_name" name="first_name" value="{{ old('first_name', $existingClient->first_name ?? $user->first_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Middle Name</label>
<input x-ref="middle_name" name="middle_name" value="{{ old('middle_name', $existingClient->middle_name ?? $user->middle_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Extension</label>
<input x-ref="extension_name" name="extension_name" value="{{ old('extension_name', $existingClient->extension_name ?? $user->extension_name ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
<div>
<label class="text-xs text-gray-500">Address</label>
<input x-ref="full_address" name="full_address" value="{{ old('full_address', $existingClient->full_address ?? $user->full_address ?? $user->address ?? '') }}" class="border p-2 rounded-lg w-full">
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
<option value="Male" {{ old('sex', $existingClient->sex ?? $user->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
<option value="Female" {{ old('sex', $existingClient->sex ?? $user->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
</select>
</div>
</div>
<div>
<label class="text-xs text-gray-500">Birthdate</label>
<input x-ref="birthdate" type="date" name="birthdate" value="{{ old('birthdate', $existingClient->birthdate ?? $user->birthdate ?? '') }}" class="border p-2 rounded-lg w-full">
</div>
<div>
<label class="text-xs text-gray-500">Civil Status</label>
<div class="select-shell">
<select name="civil_status" class="form-select">
<option value="">Select</option>
<option value="Single" {{ old('civil_status', $existingClient->civil_status ?? $user->civil_status ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
<option value="Married" {{ old('civil_status', $existingClient->civil_status ?? $user->civil_status ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
<option value="Widowed" {{ old('civil_status', $existingClient->civil_status ?? $user->civil_status ?? '') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
<option value="Separated" {{ old('civil_status', $existingClient->civil_status ?? $user->civil_status ?? '') == 'Separated' ? 'selected' : '' }}>Separated</option>
</select>
</div>
</div>
</div>

<div class="mt-4">
<label class="text-xs text-gray-500">Relationship to Beneficiary</label>
<div class="select-shell">
<select name="relationship_id" x-model="relationship" @change="handleRelationshipChange()" class="form-select">
<option value="">Select</option>
@foreach($relationships as $rel)
<option value="{{ $rel->id }}">{{ $rel->name }}</option>
@endforeach
</select>
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

            <div class="select-shell">
            <select name="family_relationship[]" x-model="member.relationship" class="form-select !bg-white !py-2 !text-sm">
                <option value="">Select</option>
                @foreach($familyRelationships as $rel)
                    <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                @endforeach
            </select>
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
    <div>
        <label class="text-xs font-semibold tracking-wide text-slate-500">Type of Assistance</label>
        <div class="select-shell mt-1">
            <select name="assistance_type_id" x-model="selectedType" @change="handleTypeChange()" class="form-select">
                <option value="">Select assistance type</option>
                @foreach($assistanceTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold tracking-wide text-slate-500">Specific Assistance</label>
        <div class="select-shell mt-1">
            <select name="assistance_subtype_id" x-model="selectedSubtype" @change="handleSubtypeChange()" class="form-select" :disabled="filteredSubtypes().length === 0">
                <option value="" x-text="selectedType ? 'Select specific assistance' : 'Choose a type first'"></option>
                <template x-for="subtype in filteredSubtypes()" :key="subtype.id">
                    <option :value="subtype.id" x-text="subtype.name"></option>
                </template>
            </select>
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold tracking-wide text-slate-500">Assistance Detail</label>
        <div class="select-shell mt-1">
            <select name="assistance_detail_id" x-model="selectedDetail" class="form-select" :disabled="!selectedSubtypeHasDetails()">
                <option value="" x-text="selectedSubtypeHasDetails() ? 'Select assistance detail' : 'No detail required for this assistance'"></option>
                <template x-for="detail in currentDetails()" :key="detail.id">
                    <option :value="detail.id" x-text="detail.name"></option>
                </template>
            </select>
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold tracking-wide text-slate-500">Mode of Assistance</label>
        <div class="select-shell mt-1">
            <select name="mode_of_assistance_id" class="form-select">
                <option value="">Select mode of assistance</option>
                @foreach($modesOfAssistance as $mode)
                <option value="{{ $mode->id }}" {{ old('mode_of_assistance_id') == $mode->id ? 'selected' : '' }}>{{ $mode->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<label class="text-xs text-gray-500">Upload Documents</label>
<input type="file" name="documents[]" multiple class="border p-2 rounded-lg w-full">

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
        assistanceTypesData: @js($assistanceTypes->map(fn ($type) => [
            'id' => (string) $type->id,
            'name' => $type->name,
            'subtypes' => $type->subtypes->map(fn ($subtype) => [
                'id' => (string) $subtype->id,
                'name' => $subtype->name,
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
        selectedType: @js((string) old('assistance_type_id', '')),
        selectedSubtype: @js((string) old('assistance_subtype_id', '')),
        selectedDetail: @js((string) old('assistance_detail_id', '')),
        relationship: @js((string) old('relationship_id', '')),
        clientFamily: @js($clientFamily),
        familyRows: @js($oldFamily ?? $clientFamily->values()),
        loadedProfileName: '',
        lookupUrl: @js(route('client.beneficiary-profile.lookup')),
        csrfToken: @js(csrf_token()),
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

            this.handleTypeChange(false);
            this.handleSubtypeChange(false);
        },

        handleTypeChange(resetSubtype = true) {
            if (resetSubtype) {
                this.selectedSubtype = '';
                this.selectedDetail = '';
            }
        },

        handleSubtypeChange(resetDetail = true) {
            if (resetDetail) {
                this.selectedDetail = '';
            }

            if (!this.selectedSubtypeHasDetails()) {
                this.selectedDetail = '';
            }
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

        filteredSubtypes() {
            const type = this.assistanceTypesData.find((item) => item.id === this.selectedType);
            return type?.subtypes || [];
        },

        currentDetails() {
            return this.currentSubtype()?.details || [];
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
                const documents = document.querySelector('input[name="documents[]"]')?.files?.length ?? 0;
                const frequencyRule = this.currentFrequencyRule();
                const frequencyDate = document.querySelector('input[name="frequency_reference_date"]')?.value;
                const frequencyCaseKey = document.querySelector('input[name="frequency_case_key"]')?.value;

                const needsDetail = this.selectedSubtypeHasDetails();
                const valid = [assistanceType, assistanceSubtype, mode].every((value) => String(value || '').trim() !== '')
                    && (!needsDetail || String(assistanceDetail || '').trim() !== '')
                    && documents > 0;
                this.errors.step4 = valid ? '' : 'Select the assistance details and upload at least one document before submitting.';
                return valid;
            }

            return true;
        },
    };
}
</script>

@endsection
