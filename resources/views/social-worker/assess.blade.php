@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

<!-- HEADER -->
<div>
    <a href="/social-worker/dashboard" class="text-sm text-gray-600 mb-2 inline-block">
        ← BACK TO DASHBOARD
    </a>

    <h1 class="text-3xl font-bold text-[#234E70]">
        Initial Assessment
    </h1>

    <p class="text-gray-500">
        Reference No: {{ $application->reference_no }}
    </p>
</div>

<!-- STEPS -->
<div class="grid grid-cols-3 gap-4">

    <div id="step-indicator-1" onclick="goToStep(1)" class="step-card cursor-pointer w-full text-left">
        <p class="text-xs uppercase">Step 1</p>
        <p class="font-semibold">Client/Beneficiary Information</p>
    </div>

    <div id="step-indicator-2" onclick="goToStep(2)" class="step-card cursor-pointer w-full text-left">
        <p class="text-xs uppercase">Step 2</p>
        <p class="font-semibold">Assistance & Documents</p>
    </div>

    <div id="step-indicator-3" onclick="goToStep(3)" class="step-card cursor-pointer w-full text-left">
        <p class="text-xs uppercase">Step 3</p>
        <p class="font-semibold">Notes & Schedule Setting</p>
    </div>

</div>

<!-- STEP 1 -->
<div id="step-1" class="step-content bg-white p-6 rounded-xl shadow space-y-6">

<!-- CLIENT INFO -->
<div>
<h2 class="text-lg font-bold text-[#234E70]">Client Information</h2>

<div class="grid grid-cols-4 gap-4">

<div>
<label class="text-sm text-gray-600">Last Name</label>
<input class="input w-full" value="{{ $application->client->last_name }}">
</div>

<div>
<label class="text-sm text-gray-600">First Name</label>
<input class="input w-full" value="{{ $application->client->first_name }}">
</div>

<div>
<label class="text-sm text-gray-600">Middle Name</label>
<input class="input w-full" value="{{ $application->client->middle_name }}">
</div>

<div>
<label class="text-sm text-gray-600">Extension</label>
<input class="input w-full" value="{{ $application->client->extension_name }}">
</div>

</div>

<div class="grid grid-cols-2 gap-4 mt-4">

<div>
<label class="text-sm text-gray-600">Address</label>
<input class="input w-full" value="{{ $application->client->full_address }}">
</div>

<div>
<label class="text-sm text-gray-600">Contact Number</label>
<input class="input w-full" value="{{ $application->client->contact_number }}">
</div>

</div>

<div class="grid grid-cols-3 gap-4 mt-4">

<div>
<label class="text-sm text-gray-600">Sex</label>
<select name="client_sex" class="input w-full">
<option value="Male" {{ $application->client->sex == 'Male' ? 'selected' : '' }}>Male</option>
<option value="Female" {{ $application->client->sex == 'Female' ? 'selected' : '' }}>Female</option>
</select>
</div>

<div>
<label class="text-sm text-gray-600">Birthdate</label>
<input type="date" class="input w-full" value="{{ $application->client->birthdate }}">
</div>

<div>
<label class="text-sm text-gray-600">Civil Status</label>
<select name="client_civil_status" class="input w-full">
<option value="Single" {{ $application->client->civil_status == 'Single' ? 'selected' : '' }}>Single</option>
<option value="Married" {{ $application->client->civil_status == 'Married' ? 'selected' : '' }}>Married</option>
<option value="Widowed" {{ $application->client->civil_status == 'Widowed' ? 'selected' : '' }}>Widowed</option>
</select>
</div>

</div>

</div>

<hr>

<!-- BENEFICIARY INFO -->
<div>
<h2 class="text-lg font-bold text-[#234E70]">Beneficiary Information</h2>

<div class="grid grid-cols-4 gap-4">

<div>
<label class="text-sm text-gray-600">Last Name</label>
<input class="input w-full" value="{{ $application->beneficiary->last_name ?? '' }}">
</div>

<div>
<label class="text-sm text-gray-600">First Name</label>
<input class="input w-full" value="{{ $application->beneficiary->first_name ?? '' }}">
</div>

<div>
<label class="text-sm text-gray-600">Middle Name</label>
<input class="input w-full" value="{{ $application->beneficiary->middle_name ?? '' }}">
</div>

<div>
<label class="text-sm text-gray-600">Extension</label>
<input class="input w-full" value="{{ $application->beneficiary->extension_name ?? '' }}">
</div>

</div>

<div class="grid grid-cols-3 gap-4 mt-4">

<div>
<label class="text-sm text-gray-600">Sex</label>
<select class="input w-full">
<option>{{ $application->beneficiary->sex ?? '' }}</option>
</select>
</div>

<div>
<label class="text-sm text-gray-600">Birthdate</label>
<input type="date" class="input w-full" value="{{ $application->beneficiary->birthdate ?? '' }}">
</div>

<div>
<label class="text-sm text-gray-600">Contact Number</label>
<input class="input w-full" value="{{ $application->beneficiary->contact_number ?? '' }}">
</div>

</div>

<div class="mt-4">
<label class="text-sm text-gray-600">Address</label>
<input class="input w-full" value="{{ $application->beneficiary->full_address ?? '' }}">
</div>

</div>

<hr>

<!-- FAMILY COMPOSITION -->
<!-- HEADER -->
<div class="mb-4 flex justify-between items-center">
    <h2 class="text-lg font-bold text-[#234E70]">Family Composition</h2>

    <button type="button"
    onclick="addFamily()"
    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
    + Add Family Member
    </button>
</div>

<!-- TABLE HEADER -->
<div class="bg-gray-100 rounded-xl px-5 py-3 grid grid-cols-5 text-xs text-gray-500 font-semibold">
    <div class="col-span-2">FULL NAME</div>
    <div>RELATIONSHIP</div>
    <div>DATE OF BIRTH</div>
    <div class="text-center">ACTIONS</div>
</div>

<!-- BODY -->
<div id="familyContainer" class="space-y-2 mt-2">

@foreach($application->familyMembers ?? [] as $index => $member)

<div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

<input type="hidden" name="family[{{ $index }}][id]" value="{{ $member->id }}">

<!-- NAME -->
<div class="col-span-2 grid grid-cols-3 gap-2">
<input name="family[{{ $index }}][last_name]" class="input text-sm" placeholder="Last" value="{{ $member->last_name }}">
<input name="family[{{ $index }}][first_name]" class="input text-sm" placeholder="First" value="{{ $member->first_name }}">
<input name="family[{{ $index }}][middle_name]" class="input text-sm" placeholder="Middle" value="{{ $member->middle_name }}">
</div>

<!-- RELATIONSHIP -->
<div>
<select name="family[{{ $index }}][relationship]" class="input text-sm w-full">
@foreach($relationships as $rel)
<option value="{{ $rel->id }}"
{{ $member->relationship == $rel->id ? 'selected' : '' }}>
{{ $rel->name }}
</option>
@endforeach
</select>
</div>

<!-- BIRTHDATE -->
<div>
<input type="date"
name="family[{{ $index }}][birthdate]"
class="input text-sm w-full"
value="{{ $member->birthdate }}">
</div>

<!-- ACTION -->
<div class="flex justify-center">
<button type="button"
onclick="removeRow(this)"
class="text-red-500 hover:text-red-700">

<!-- MATERIAL ICON -->
<span class="material-symbols-outlined text-[20px]">
delete
</span>

</button>
</div>

</div>

@endforeach

</div>

</div>

<!-- STEP 2 -->
<div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

<!-- ASSISTANCE INFO -->
<div>
<h2 class="font-bold text-[#0B3C5D] mb-4">Assistance Information</h2>

<div class="grid grid-cols-3 gap-4">

<div>
<label class="text-sm text-gray-600">Type of Assistance</label>
<select name="assistance_type_id" class="input w-full">

@foreach($assistanceTypes as $type)
<option value="{{ $type->id }}"
{{ $application->assistance_type_id == $type->id ? 'selected' : '' }}>
{{ $type->name }}
</option>
@endforeach

</select>
</div>

<div>
<label class="text-sm text-gray-600">Specific Assistance</label>
<select name="assistance_subtype_id" class="input w-full">

@foreach($assistanceSubtypes as $sub)
<option value="{{ $sub->id }}"
{{ $application->assistance_subtype_id == $sub->id ? 'selected' : '' }}>
{{ $sub->name }}
</option>
@endforeach

</select>
</div>

<div>
<label class="text-sm text-gray-600">Mode of Assistance</label>
<select name="mode_of_assistance" class="input w-full">
<option value="Cash" {{ $application->mode_of_assistance == 'Cash' ? 'selected' : '' }}>Cash</option>
<option value="Guarantee Letter" {{ $application->mode_of_assistance == 'gl' ? 'selected' : '' }}>Guarantee Letter</option>
<option value="Referral" {{ $application->mode_of_assistance == 'Referral' ? 'selected' : '' }}>Referral</option>
</select>
</div>

</div>

</div>

<hr>

<!-- DOCUMENTS -->
<div>
<h2 class="font-bold text-[#0B3C5D] mb-4">Uploaded Documents</h2>

@forelse($application->documents ?? [] as $file)

<div class="bg-gray-50 p-4 rounded-lg flex items-center justify-between">

<!-- LEFT: VIEW + FILE -->
<div class="flex items-center gap-3">

<a href="{{ $file->file_path ?? $file->path }}" target="_blank"
class="px-3 py-2 bg-gray-200 rounded-lg text-sm hover:bg-gray-300">
View
</a>

<p class="font-medium text-gray-700">
{{ $file->file_name ?? $file->filename }}
</p>

</div>

<!-- RIGHT: REMARKS -->
<div class="w-72">

<input type="text"
name="remarks[{{ $file->id }}]"
value="{{ $file->remarks ?? '' }}"
class="input w-full"
placeholder="Add remarks...">

</div>

</div>

@empty

<p class="text-gray-400">No documents uploaded.</p>

@endforelse

</div>

</div>

<!-- STEP 3 -->
<div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-4">

<h2 class="font-bold text-[#0B3C5D]">Notes & Schedule</h2>

<div>
<label class="text-sm text-gray-600">Assessment Notes</label>
<textarea class="input w-full h-32"></textarea>
</div>

<div class="grid grid-cols-2 gap-4">

<div>
<label class="text-sm text-gray-600">Schedule</label>
<input type="datetime-local" class="input">
</div>

<div>
<label class="text-sm text-gray-600">Meeting Link</label>
<input type="text" class="input">
</div>

</div>

</div>

<!-- ACTION BUTTONS -->
<div class="flex justify-between items-center">

<button id="backBtn"
class="px-5 py-2 bg-gray-200 rounded-lg hidden">
← Back
</button>

<div class="ml-auto">
<button id="nextBtn"
class="px-6 py-3 bg-[#0B3C5D] text-white rounded-lg shadow-sm hover:bg-[#0a2f47] transition">
Next →
</button>
</div>

</div>

</main>

<!-- SCRIPT -->
<script>
let currentStep = 1;
const totalSteps = 3;

function updateSteps() {

    for (let i = 1; i <= totalSteps; i++) {
        let step = document.getElementById('step-' + i);
        let indicator = document.getElementById('step-indicator-' + i);

        step.classList.add('opacity-0', 'translate-x-4');
        step.classList.add('hidden');

        indicator.classList.remove('active');
    }

    let activeStep = document.getElementById('step-' + currentStep);

    activeStep.classList.remove('hidden');

    setTimeout(() => {
        activeStep.classList.remove('opacity-0', 'translate-x-4');
    }, 50);

    document.getElementById('step-indicator-' + currentStep).classList.add('active');

    document.getElementById('backBtn').style.display =
        currentStep === 1 ? 'none' : 'inline-block';

    document.getElementById('nextBtn').innerText =
        currentStep === totalSteps ? 'Save Assessment' : 'Next →';
}

document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentStep < totalSteps) {
        currentStep++;
        updateSteps();
    } else {
        alert('Save logic here 🔥');
    }
});

document.getElementById('backBtn').addEventListener('click', () => {
    currentStep--;
    updateSteps();
});

updateSteps();

function goToStep(step) {
    currentStep = step;
    updateSteps();
}
</script>
<script>
let familyIndex = {{ count($application->familyMembers ?? []) }};

function addFamily() {

    let container = document.getElementById('familyContainer');

    let html = `
    <div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

        <div class="col-span-2 grid grid-cols-3 gap-2">
            <input name="family[${familyIndex}][last_name]" class="input text-sm" placeholder="Last">
            <input name="family[${familyIndex}][first_name]" class="input text-sm" placeholder="First">
            <input name="family[${familyIndex}][middle_name]" class="input text-sm" placeholder="Middle">
        </div>

        <div>
            <select name="family[${familyIndex}][relationship]" class="input text-sm w-full">
                @foreach($relationships as $rel)
                    <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <input type="date" name="family[${familyIndex}][birthdate]" class="input text-sm w-full">
        </div>

        <div class="flex justify-center">
            <button type="button"
            onclick="removeRow(this)"
            class="text-red-500 hover:text-red-700">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        </div>

    </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    familyIndex++;
}

function removeRow(btn) {
    btn.closest('.family-row').remove();
}
</script>
<!-- STYLE -->
<style>
.step-card {
    background: #f1f5f9;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    color: #64748b;
    transition: all 0.2s ease;
}

.step-card:hover {
    background: #e2e8f0;
}

.step-card.active {
    background: #0B3C5D;
    color: white;
    border: none;
}
</style>

@endsection