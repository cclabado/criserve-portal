@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto py-8"
     x-data="{ step: 1, selectedType: '', relationship: '' }">

<!-- HEADER -->
<div class="mb-6">
    <a href="/client/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
        ← BACK TO DASHBOARD
    </a>

    <h1 class="text-3xl font-bold text-[#234E70]">
        New Assistance Application
    </h1>

    <p class="text-gray-500">
        Assistance to Individuals in Crisis Situation
    </p>
</div>

<!-- STEPPER -->
<div class="flex gap-4 mb-8">
    <div @click="step = 1" :class="step == 1 ? 'bg-[#234E70] text-white' : 'bg-gray-100 text-gray-500'" class="px-6 py-4 rounded-xl w-64 cursor-pointer">
        <p class="text-xs">STEP 1</p><p class="font-semibold">Personal Info</p>
    </div>

    <div @click="step = 2" :class="step == 2 ? 'bg-[#234E70] text-white' : 'bg-gray-100 text-gray-500'" class="px-6 py-4 rounded-xl w-64 cursor-pointer">
        <p class="text-xs">STEP 2</p><p class="font-semibold">Beneficiary Info</p>
    </div>

    <div @click="step = 3" :class="step == 3 ? 'bg-[#234E70] text-white' : 'bg-gray-100 text-gray-500'" class="px-6 py-4 rounded-xl w-64 cursor-pointer">
        <p class="text-xs">STEP 3</p><p class="font-semibold">Family Composition</p>
    </div>

    <div @click="step = 4" :class="step == 4 ? 'bg-[#234E70] text-white' : 'bg-gray-100 text-gray-500'" class="px-6 py-4 rounded-xl w-64 cursor-pointer">
        <p class="text-xs">STEP 4</p><p class="font-semibold">Assistance & Document</p>
    </div>
</div>

<form method="POST" action="/client/application" enctype="multipart/form-data">
@csrf

<!-- ================= STEP 1 ================= -->
<div x-show="step == 1">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Client Information</h2>

<div class="grid grid-cols-4 gap-4 mb-4">

<div>
<label class="text-xs text-gray-500">Last Name</label>
<input x-ref="last_name" name="last_name" class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">First Name</label>
<input x-ref="first_name" name="first_name" class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Middle Name</label>
<input x-ref="middle_name" name="middle_name" class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Extension</label>
<input x-ref="extension_name" name="extension_name" class="border p-2 rounded-lg w-full">
</div>

</div>

<div class="grid grid-cols-2 gap-4 mb-4">

<div>
<label class="text-xs text-gray-500">Address</label>
<input x-ref="full_address" name="full_address" class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Contact Number</label>
<input x-ref="contact_number" name="contact_number" class="border p-2 rounded-lg w-full">
</div>

</div>

<div class="grid grid-cols-3 gap-4">

<div>
<label class="text-xs text-gray-500">Sex</label>
<select x-ref="sex" name="sex" class="border p-2 rounded-lg w-full">
<option value="">Select</option>
<option>Male</option>
<option>Female</option>
</select>
</div>

<div>
<label class="text-xs text-gray-500">Birthdate</label>
<input x-ref="birthdate" type="date" name="birthdate" class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Civil Status</label>
<select name="civil_status" class="border p-2 rounded-lg w-full">
<option value="">Select</option>
<option>Single</option>
<option>Married</option>
<option>Widowed</option>
<option>Separated</option>
</select>
</div>

</div>

<div class="mt-4">
<label class="text-xs text-gray-500">Relationship to Beneficiary</label>
<select name="relationship_id" x-model="relationship" class="border p-2 rounded-lg w-full">
<option value="">Select</option>
@foreach(\App\Models\Relationship::all() as $rel)
<option value="{{ $rel->id }}">{{ $rel->name }}</option>
@endforeach
</select>
</div>

<div class="text-right mt-6">
<button type="button"
@click="relationship == 1 ? step = 3 : step = 2"
class="bg-[#234E70] text-white px-6 py-3 rounded-xl">
Continue →
</button>
</div>

</div>
</div>

<!-- ================= STEP 2 ================= -->
<div x-show="step == 2">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Beneficiary Information</h2>

<div class="grid grid-cols-4 gap-4 mb-4">

<div>
<label class="text-xs text-gray-500">Last Name</label>
<input name="bene_last_name"
:value="relationship == 1 ? $refs.last_name.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">First Name</label>
<input name="bene_first_name"
:value="relationship == 1 ? $refs.first_name.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Middle Name</label>
<input name="bene_middle_name"
:value="relationship == 1 ? $refs.middle_name.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Extension</label>
<input name="bene_extension_name"
:value="relationship == 1 ? $refs.extension_name.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

</div>

<div class="grid grid-cols-3 gap-4 mb-4">

<div>
<label class="text-xs text-gray-500">Sex</label>
<select name="bene_sex"
x-bind:value="relationship == 1 ? $refs.sex.value : ''"
:disabled="relationship == 1"
class="border p-2 rounded-lg w-full">
<option value="">Select</option>
<option>Male</option>
<option>Female</option>
</select>
</div>

<div>
<label class="text-xs text-gray-500">Birthdate</label>
<input type="date"
name="bene_birthdate"
:value="relationship == 1 ? $refs.birthdate.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

<div>
<label class="text-xs text-gray-500">Contact Number</label>
<input name="bene_contact_number"
:value="relationship == 1 ? $refs.contact_number.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

</div>

<div class="grid grid-cols-1 gap-4">

<div>
<label class="text-xs text-gray-500">Address</label>
<input name="bene_full_address"
:value="relationship == 1 ? $refs.full_address.value : ''"
:readonly="relationship == 1"
class="border p-2 rounded-lg w-full">
</div>

</div>

<div class="flex justify-between mt-6">
<button type="button" @click="step = 1"
class="bg-gray-200 px-6 py-3 rounded-xl">
← Back
</button>

<button type="button" @click="step = 3"
class="bg-[#234E70] text-white px-6 py-3 rounded-xl">
Continue →
</button>
</div>

</div>
</div>

<!-- ================= STEP 3 ================= -->
<div x-show="step == 3">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<div class="flex justify-between items-center mb-4">
<h2 class="font-bold">Step 3: Family Composition</h2>
<button type="button" onclick="addRow()" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-xl">
+ Add Family Member
</button>
</div>

<div class="grid grid-cols-4 text-xs text-gray-500 px-4 mb-2">
<div>FULL NAME</div>
<div>RELATIONSHIP</div>
<div>DATE OF BIRTH</div>
<div class="text-center">ACTIONS</div>
</div>

<div id="family-table" class="space-y-3">

<div class="grid grid-cols-4 items-center bg-gray-100 rounded-xl px-4 py-3">

<div class="flex gap-1">
<input name="family_last_name[]" placeholder="Last" class="bg-transparent outline-none w-1/3">
<input name="family_first_name[]" placeholder="First" class="bg-transparent outline-none w-1/3">
<input name="family_middle_name[]" placeholder="Middle" class="bg-transparent outline-none w-1/3">
</div>

<select name="family_relationship[]" class="bg-transparent outline-none w-full">
    <option value="">Select</option>
    @foreach(\App\Models\Relationship::where('id', '!=', 1)->get() as $rel)
        <option value="{{ $rel->id }}">{{ $rel->name }}</option>
    @endforeach
</select>

<input type="date" name="family_birthdate[]" class="bg-transparent outline-none">

<div class="text-center">
<button type="button" onclick="removeRow(this)" class="text-red-500">🗑</button>
</div>

</div>

</div>

<div class="flex justify-between mt-6">
<button type="button" @click="step = 2" class="bg-gray-200 px-6 py-3 rounded-xl">← Back</button>
<button type="button" @click="step = 4" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">Continue →</button>
</div>

</div>
</div>

<!-- ================= STEP 4 ================= -->
<div x-show="step == 4">
<div class="bg-white p-6 rounded-2xl shadow-sm">

<h2 class="font-bold mb-4">Assistance Information</h2>

<label class="text-xs text-gray-500">Type of Assistance</label>
<select name="assistance_type_id" x-model="selectedType" class="border p-2 rounded-lg w-full mb-3">
<option value="">Select</option>
@foreach(\App\Models\AssistanceType::with('subtypes')->get() as $type)
<option value="{{ $type->id }}">{{ $type->name }}</option>
@endforeach
</select>

<label class="text-xs text-gray-500">Specific Assistance</label>
<select name="assistance_subtype_id" class="border p-2 rounded-lg w-full mb-3">
<option>Select</option>
@foreach(\App\Models\AssistanceType::with('subtypes')->get() as $type)
<template x-if="selectedType == {{ $type->id }}">
@foreach($type->subtypes as $sub)
<option value="{{ $sub->id }}">{{ $sub->name }}</option>
@endforeach
</template>
@endforeach
</select>

<label class="text-xs text-gray-500">Mode of Assistance</label>
<select name="mode_of_assistance" class="border p-2 rounded-lg w-full mb-3">
<option value="">Select</option>
<option value="cash">Cash</option>
<option value="gl">Guarantee Letter</option>
</select>

<label class="text-xs text-gray-500">Upload Documents</label>
<input type="file" name="documents[]" multiple class="border p-2 rounded-lg w-full">

<div class="flex justify-between mt-6">
<button type="button" @click="step = 3" class="bg-gray-200 px-6 py-3 rounded-xl">← Back</button>
<button type="submit" class="bg-[#234E70] text-white px-6 py-3 rounded-xl">Submit</button>
</div>

</div>
</div>

</form>

</div>

<script>
function addRow() {
    let row = `
    <div class="grid grid-cols-4 items-center bg-gray-100 rounded-xl px-4 py-3 mt-2">

        <div class="flex gap-1">
            <input name="family_last_name[]" placeholder="Last" class="bg-transparent outline-none w-1/3">
            <input name="family_first_name[]" placeholder="First" class="bg-transparent outline-none w-1/3">
            <input name="family_middle_name[]" placeholder="Middle" class="bg-transparent outline-none w-1/3">
        </div>

        <select name="family_relationship[]" class="bg-transparent outline-none w-full">
            <option value="">Select</option>
            @foreach(\App\Models\Relationship::all() as $rel)
                <option value="{{ $rel->id }}">{{ $rel->name }}</option>
            @endforeach
        </select>

        <input type="date" name="family_birthdate[]" class="bg-transparent outline-none">

        <div class="text-center">
            <button type="button" onclick="removeRow(this)" class="text-red-500">🗑</button>
        </div>

    </div>`;

    document.getElementById('family-table').insertAdjacentHTML('beforeend', row);
}

function removeRow(btn) {
    btn.closest('.grid').remove();
}
</script>

@endsection