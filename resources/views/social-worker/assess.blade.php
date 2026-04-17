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

    <form id="assessmentForm" method="POST" action="{{ route('socialworker.assess.update', $application->id) }}">
        @csrf

        <!-- STEPS -->
        <div class="grid grid-cols-3 gap-4">

            <div id="step-indicator-1" onclick="goToStep(1)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 1</p>
                <p class="font-semibold">Client / Beneficiary</p>
            </div>

            <div id="step-indicator-2" onclick="goToStep(2)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 2</p>
                <p class="font-semibold">Assistance & Documents</p>
            </div>

            <div id="step-indicator-3" onclick="goToStep(3)" class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 3</p>
                <p class="font-semibold">Notes & Schedule</p>
            </div>

        </div>

        <!-- ================= STEP 1 ================= -->
        <div id="step-1" class="step-content bg-white p-6 rounded-xl shadow space-y-6">

            <!-- CLIENT -->
            <div>
                <h2 class="text-lg font-bold text-[#234E70] mb-4">Client Information</h2>

                <div class="grid grid-cols-4 gap-4">

                    <div>
                        <label class="label">Last Name</label>
                        <input name="client_last_name" class="input w-full"
                            value="{{ $application->client->last_name }}">
                    </div>

                    <div>
                        <label class="label">First Name</label>
                        <input name="client_first_name" class="input w-full"
                            value="{{ $application->client->first_name }}">
                    </div>

                    <div>
                        <label class="label">Middle Name</label>
                        <input name="client_middle_name" class="input w-full"
                            value="{{ $application->client->middle_name }}">
                    </div>

                    <div>
                        <label class="label">Extension</label>
                        <input name="client_extension_name" class="input w-full"
                            value="{{ $application->client->extension_name }}">
                    </div>

                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">

                    <div>
                        <label class="label">Address</label>
                        <input name="client_address" class="input w-full"
                            value="{{ $application->client->full_address }}">
                    </div>

                    <div>
                        <label class="label">Contact Number</label>
                        <input name="client_contact_number" class="input w-full"
                            value="{{ $application->client->contact_number }}">
                    </div>

                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">

                    <div>
                        <label class="label">Sex</label>
                        <select name="client_sex" class="input w-full">
                            <option value="Male"
                                {{ $application->client->sex == 'Male' ? 'selected' : '' }}>
                                Male
                            </option>

                            <option value="Female"
                                {{ $application->client->sex == 'Female' ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Birthdate</label>
                        <input type="date" name="client_birthdate" class="input w-full"
                            value="{{ $application->client->birthdate }}">
                    </div>

                    <div>
                        <label class="label">Civil Status</label>
                        <select name="client_civil_status" class="input w-full">
                            <option value="Single"
                                {{ $application->client->civil_status == 'Single' ? 'selected' : '' }}>
                                Single
                            </option>

                            <option value="Married"
                                {{ $application->client->civil_status == 'Married' ? 'selected' : '' }}>
                                Married
                            </option>

                            <option value="Widowed"
                                {{ $application->client->civil_status == 'Widowed' ? 'selected' : '' }}>
                                Widowed
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <hr>

            <!-- BENEFICIARY -->
            <div>
                <h2 class="text-lg font-bold text-[#234E70] mb-4">Beneficiary Information</h2>

                <div class="grid grid-cols-4 gap-4">

                    <div>
                        <label class="label">Last Name</label>
                        <input name="beneficiary_last_name" class="input w-full"
                            value="{{ $application->beneficiary->last_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">First Name</label>
                        <input name="beneficiary_first_name" class="input w-full"
                            value="{{ $application->beneficiary->first_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Middle Name</label>
                        <input name="beneficiary_middle_name" class="input w-full"
                            value="{{ $application->beneficiary->middle_name ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Extension</label>
                        <input name="beneficiary_extension_name" class="input w-full"
                            value="{{ $application->beneficiary->extension_name ?? '' }}">
                    </div>

                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">

                    <div>
                        <label class="label">Sex</label>
                        <select name="beneficiary_sex" class="input w-full">
                            <option value="Male"
                                {{ ($application->beneficiary->sex ?? '') == 'Male' ? 'selected' : '' }}>
                                Male
                            </option>

                            <option value="Female"
                                {{ ($application->beneficiary->sex ?? '') == 'Female' ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Birthdate</label>
                        <input type="date" name="beneficiary_birthdate" class="input w-full"
                            value="{{ $application->beneficiary->birthdate ?? '' }}">
                    </div>

                    <div>
                        <label class="label">Contact Number</label>
                        <input name="beneficiary_contact_number" class="input w-full"
                            value="{{ $application->beneficiary->contact_number ?? '' }}">
                    </div>

                </div>

                <div class="mt-4">
                    <label class="label">Address</label>
                    <input name="beneficiary_address" class="input w-full"
                        value="{{ $application->beneficiary->full_address ?? '' }}">
                </div>
            </div>

            <hr>

            <!-- FAMILY -->
            <div class="mb-4 flex justify-between items-center">
                <h2 class="text-lg font-bold text-[#234E70]">Family Composition</h2>

                <button type="button"
                    onclick="addFamily()"
                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    + Add Family Member
                </button>
            </div>

            <div class="bg-gray-100 rounded-xl px-5 py-3 grid grid-cols-5 text-xs text-gray-500 font-semibold">
                <div class="col-span-2">FULL NAME</div>
                <div>RELATIONSHIP</div>
                <div>DATE OF BIRTH</div>
                <div class="text-center">ACTIONS</div>
            </div>

            <div id="familyContainer" class="space-y-2 mt-2">

                @foreach($application->familyMembers ?? [] as $index => $member)

                <div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

                    <input type="hidden" name="family[{{ $index }}][id]" value="{{ $member->id }}">

                    <div class="col-span-2 grid grid-cols-3 gap-2">
                        <input name="family[{{ $index }}][last_name]" class="input text-sm"
                            placeholder="Last" value="{{ $member->last_name }}">

                        <input name="family[{{ $index }}][first_name]" class="input text-sm"
                            placeholder="First" value="{{ $member->first_name }}">

                        <input name="family[{{ $index }}][middle_name]" class="input text-sm"
                            placeholder="Middle" value="{{ $member->middle_name }}">
                    </div>

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

                    <div>
                        <input type="date"
                            name="family[{{ $index }}][birthdate]"
                            class="input text-sm w-full"
                            value="{{ $member->birthdate }}">
                    </div>

                    <div class="flex justify-center">
                        <button type="button"
                            onclick="removeRow(this)"
                            class="text-red-500 hover:text-red-700">

                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>

                </div>

                @endforeach

            </div>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <div>
                <h2 class="font-bold text-[#234E70] mb-4">Assistance Information</h2>

                <div class="grid grid-cols-3 gap-4">

                    <div>
                        <label class="label">Type of Assistance</label>
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
                        <label class="label">Specific Assistance</label>
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
                        <label class="label">Mode of Assistance</label>
                        <select name="mode_of_assistance" class="input w-full">
                            <option value="Cash"
                                {{ $application->mode_of_assistance == 'Cash' ? 'selected' : '' }}>
                                Cash
                            </option>

                            <option value="gl"
                                {{ $application->mode_of_assistance == 'gl' ? 'selected' : '' }}>
                                Guarantee Letter
                            </option>

                            <option value="Referral"
                                {{ $application->mode_of_assistance == 'Referral' ? 'selected' : '' }}>
                                Referral
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <hr>

            <div>
                <h2 class="font-bold text-[#234E70] mb-4">Uploaded Documents</h2>

                @forelse($application->documents ?? [] as $file)

                <div class="bg-gray-50 p-4 rounded-lg flex items-center justify-between mb-3">

                    <div class="flex items-center gap-3">

                        <a href="{{ asset('storage/' . ($file->file_path ?? $file->path)) }}"
                            target="_blank"
                            class="px-3 py-2 bg-gray-200 rounded-lg text-sm hover:bg-gray-300">
                            View
                        </a>

                        <p class="font-medium text-gray-700">
                            {{ $file->file_name ?? $file->filename }}
                        </p>

                    </div>

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

        <!-- ================= STEP 3 ================= -->
        <div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-4">

            <h2 class="font-bold text-[#234E70]">Notes & Schedule</h2>

            <div>
                <label class="label">Assessment Notes</label>
                <textarea name="notes"
                    class="input w-full h-32">{{ $application->notes }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Schedule Date</label>
                    <input type="datetime-local"
                        name="schedule_date"
                        class="input w-full"
                        value="{{ $application->schedule_date ? \Carbon\Carbon::parse($application->schedule_date)->format('Y-m-d\TH:i') : '' }}">
                </div>

                <div>
                    <label class="label">Meeting Link</label>
                    <input type="text"
                        name="meeting_link"
                        class="input w-full"
                        value="{{ $application->meeting_link }}">
                </div>

            </div>

        </div>

        <!-- BUTTONS -->
        <div class="flex justify-between items-center">

            <button type="button"
                id="backBtn"
                class="px-5 py-2 bg-gray-200 rounded-lg hidden">
                ← Back
            </button>

            <button type="button"
                id="nextBtn"
                class="px-6 py-3 bg-[#234E70] text-white rounded-lg shadow hover:bg-[#18384f]">
                Next →
            </button>

        </div>

    </form>

</main>

<!-- JS -->
<script>
let currentStep = 1;
const totalSteps = 3;

function updateSteps() {

    for (let i = 1; i <= totalSteps; i++) {
        document.getElementById('step-' + i).classList.add('hidden');
        document.getElementById('step-indicator-' + i).classList.remove('active');
    }

    document.getElementById('step-' + currentStep).classList.remove('hidden');
    document.getElementById('step-indicator-' + currentStep).classList.add('active');

    document.getElementById('backBtn').style.display =
        currentStep === 1 ? 'none' : 'inline-block';

    document.getElementById('nextBtn').innerText =
        currentStep === totalSteps ? 'Save Assessment' : 'Next →';
}

function goToStep(step) {
    currentStep = step;
    updateSteps();
}

document.getElementById('nextBtn').addEventListener('click', function () {

    if (currentStep < totalSteps) {
        currentStep++;
        updateSteps();
    } else {
        document.getElementById('assessmentForm').submit();
    }
});

document.getElementById('backBtn').addEventListener('click', function () {
    currentStep--;
    updateSteps();
});

updateSteps();

let familyIndex = {{ count($application->familyMembers ?? []) }};

function addFamily() {

    let html = `
    <div class="family-row bg-gray-50 rounded-xl px-5 py-3 grid grid-cols-5 gap-3 items-center">

        <div class="col-span-2 grid grid-cols-3 gap-2">
            <input name="family[\${familyIndex}][last_name]" class="input text-sm" placeholder="Last">
            <input name="family[\${familyIndex}][first_name]" class="input text-sm" placeholder="First">
            <input name="family[\${familyIndex}][middle_name]" class="input text-sm" placeholder="Middle">
        </div>

        <div>
            <select name="family[\${familyIndex}][relationship]" class="input text-sm w-full">
                @foreach($relationships as $rel)
                    <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <input type="date" name="family[\${familyIndex}][birthdate]" class="input text-sm w-full">
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

    document.getElementById('familyContainer').insertAdjacentHTML('beforeend', html);
    familyIndex++;
}

function removeRow(btn) {
    btn.closest('.family-row').remove();
}
</script>

<!-- STYLE -->
<style>
.step-card{
    background:#f1f5f9;
    padding:16px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    color:#64748b;
    transition:.2s;
    min-height:75px;
}
.step-card:hover{
    background:#e2e8f0;
}
.step-card.active{
    background:#234E70;
    color:white;
    border:none;
}
.label{
    font-size:14px;
    color:#4b5563;
    display:block;
    margin-bottom:6px;
}
.input{
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:10px 12px;
    outline:none;
}
.input:focus{
    border-color:#234E70;
    box-shadow:0 0 0 2px rgba(35,78,112,.12);
}
</style>

@endsection