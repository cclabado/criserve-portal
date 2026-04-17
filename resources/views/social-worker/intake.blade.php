@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div>
        <a href="{{ route('socialworker.applications') }}"
           class="text-sm text-gray-500 hover:text-[#234E70]">
            ← Back to Applications
        </a>

        <h1 class="text-3xl font-bold text-[#234E70] mt-2">
            Intake Assessment
        </h1>

        <p class="text-gray-500">
            Reference: {{ $application->reference_no }}
        </p>
    </div>
    <!-- CLIENT SUMMARY -->
    <div class="bg-white p-6 rounded-xl shadow space-y-6">

        <div>
            <h2 class="text-lg font-bold text-[#234E70]">Client Information</h2>

            <div class="grid grid-cols-4 gap-4 mt-4 text-sm">
                <div><span class="text-gray-500">Name</span><br>
                    {{ $application->client->last_name }},
                    {{ $application->client->first_name }}
                </div>

                <div><span class="text-gray-500">Sex</span><br>
                    {{ $application->client->sex }}
                </div>

                <div><span class="text-gray-500">Birthdate</span><br>
                    {{ $application->client->birthdate }}
                </div>

                <div><span class="text-gray-500">Civil Status</span><br>
                    {{ $application->client->civil_status }}
                </div>
            </div>

            <div class="mt-3 text-sm">
                <span class="text-gray-500">Address</span><br>
                {{ $application->client->full_address }}
            </div>
        </div>

        @if($application->beneficiary)
        <hr>

        <div>
            <h2 class="text-lg font-bold text-[#234E70]">Beneficiary Information</h2>

            <div class="grid grid-cols-4 gap-4 mt-4 text-sm">
                <div><span class="text-gray-500">Name</span><br>
                    {{ $application->beneficiary->last_name }},
                    {{ $application->beneficiary->first_name }}
                </div>

                <div><span class="text-gray-500">Sex</span><br>
                    {{ $application->beneficiary->sex }}
                </div>

                <div><span class="text-gray-500">Birthdate</span><br>
                    {{ $application->beneficiary->birthdate }}
                </div>

                <div><span class="text-gray-500">Contact</span><br>
                    {{ $application->beneficiary->contact_number }}
                </div>
            </div>
        </div>
        @endif

    </div>
    <form id="intakeForm"
          method="POST"
          action="{{ route('socialworker.intake.save', $application->id) }}">

        @csrf

        <!-- STEP CARDS -->
        <div class="grid grid-cols-3 gap-4">

            <div id="step-indicator-1"
                 onclick="goToStep(1)"
                 class="step-card active cursor-pointer">
                <p class="text-xs uppercase">Step 1</p>
                <p class="font-semibold">Financial Status</p>
            </div>

            <div id="step-indicator-2"
                 onclick="goToStep(2)"
                 class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 2</p>
                <p class="font-semibold">Crisis & Risk</p>
            </div>

            <div id="step-indicator-3"
                 onclick="goToStep(3)"
                 class="step-card cursor-pointer">
                <p class="text-xs uppercase">Step 3</p>
                <p class="font-semibold">Recommendation</p>
            </div>

        </div>

        <!-- ================= STEP 1 ================= -->
        <div id="step-1" class="step-content bg-white p-6 rounded-xl shadow space-y-6">

            <h2 class="text-lg font-bold text-[#234E70]">
                Financial Capacity
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Combined Monthly Income</label>
                    <input type="number"
                           step="0.01"
                           name="monthly_income"
                           class="input w-full"
                           value="{{ $application->monthly_income }}">
                </div>

                <div>
                    <label class="label">Monthly Expenses</label>
                    <input type="number"
                           step="0.01"
                           name="monthly_expenses"
                           class="input w-full"
                           value="{{ $application->monthly_expenses }}">
                </div>

                <div>
                    <label class="label">Household Members</label>
                    <input type="number"
                           name="household_members"
                           class="input w-full"
                           value="{{ $application->household_members }}">
                </div>

                <div>
                    <label class="label">Working Members</label>
                    <input type="number"
                           name="working_members"
                           class="input w-full"
                           value="{{ $application->working_members }}">
                </div>

                <div class="col-span-2">
                    <label class="label">Savings Available</label>
                    <input type="number"
                           step="0.01"
                           name="savings"
                           class="input w-full"
                           value="{{ $application->savings }}">
                </div>

            </div>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div id="step-2" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <h2 class="text-lg font-bold text-[#234E70]">
                Crisis Severity & Risk Factors
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Type of Crisis</label>
                    <select name="crisis_type" class="input w-full">
                        <option value="">Select</option>
                        <option value="Hospitalization">Hospitalization</option>
                        <option value="Death">Death in Family</option>
                        <option value="Disaster">Fire/Flood/Disaster</option>
                        <option value="Loss of Livelihood">Loss of Livelihood</option>
                        <option value="Food Need">Food Need</option>
                        <option value="Education Need">Education Need</option>
                    </select>
                </div>

                <div>
                    <label class="label">Urgency Level</label>
                    <select name="urgency_level" class="input w-full">
                        <option value="">Select</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-4">

                <label class="check-box">
                    <input type="checkbox" name="has_elderly">
                    Elderly in Household
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_child">
                    Child in Need
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_pwd">
                    Person with Disability
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_pregnant">
                    Pregnant Household Member
                </label>

                <label class="check-box">
                    <input type="checkbox" name="earner_unable_to_work">
                    Main Earner Unable to Work
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_philhealth">
                    Has PhilHealth / Health Card
                </label>

                <label class="check-box">
                    <input type="checkbox" name="has_family_support">
                    Has Family Support
                </label>

            </div>

        </div>

        <!-- ================= STEP 3 ================= -->
        <div id="step-3" class="step-content hidden bg-white p-6 rounded-xl shadow space-y-6">

            <h2 class="text-lg font-bold text-[#234E70]">
                Recommendation
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="label">Recommended Amount</label>
                    <input type="number"
                        id="recommended_amount"
                        name="recommended_amount"
                        class="input w-full bg-gray-100"
                        readonly>
                </div>

                <div>
                    <label class="label">Final Amount</label>
                    <input type="number"
                        step="0.01"
                        name="final_amount"
                        class="input w-full"
                        value="{{ $application->final_amount }}">
                </div>

            </div>

            <div>
                <label class="label">Problem Statement</label>
                <textarea name="problem_statement"
                        class="input w-full h-28">{{ $application->problem_statement }}</textarea>
            </div>

            <div>
                <label class="label">Social Worker Assessment</label>
                <textarea name="social_worker_assessment"
                        class="input w-full h-32">{{ $application->social_worker_assessment }}</textarea>
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
                    class="px-6 py-3 bg-[#234E70] text-white rounded-lg hover:bg-[#18384f]">
                Next →
            </button>

        </div>

    </form>

</main>

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
        currentStep === totalSteps ? 'Save Intake' : 'Next →';
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
        document.getElementById('intakeForm').submit();
    }
});

document.getElementById('backBtn').addEventListener('click', function () {
    currentStep--;
    updateSteps();
});

updateSteps();
</script>
<script>
    function computeRecommendation() {

    let income = parseFloat(document.querySelector('[name="monthly_income"]').value) || 0;
    let expenses = parseFloat(document.querySelector('[name="monthly_expenses"]').value) || 0;
    let savings = parseFloat(document.querySelector('[name="savings"]').value) || 0;

    let urgency = document.querySelector('[name="urgency_level"]').value;
    let crisis = document.querySelector('[name="crisis_type"]').value;

    let score = 0;

    if (income < 10000) score += 3;
    else if (income < 20000) score += 2;

    if (expenses > income) score += 2;
    if (savings <= 0) score += 1;

    if (urgency === 'Critical') score += 4;
    else if (urgency === 'High') score += 3;
    else if (urgency === 'Medium') score += 2;

    if (['Hospitalization','Death','Disaster'].includes(crisis)) score += 3;

    if (document.querySelector('[name="has_elderly"]').checked) score++;
    if (document.querySelector('[name="has_child"]').checked) score++;
    if (document.querySelector('[name="has_pwd"]').checked) score++;
    if (document.querySelector('[name="has_pregnant"]').checked) score++;
    if (document.querySelector('[name="earner_unable_to_work"]').checked) score += 2;

    if (!document.querySelector('[name="has_family_support"]').checked) score += 2;

    let amount = 3000;

    if (score <= 3) amount = 3000;
    else if (score <= 6) amount = 5000;
    else if (score <= 9) amount = 8000;
    else if (score <= 12) amount = 10000;
    else amount = 15000;

    document.getElementById('recommended_amount').value = amount;
}

document.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('input', computeRecommendation);
    el.addEventListener('change', computeRecommendation);
});

computeRecommendation();
</script>
<style>
.step-card{
    background:#f1f5f9;
    padding:16px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    color:#64748b;
    min-height:75px;
}
.step-card.active{
    background:#234E70;
    color:white;
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
    width:100%;
}
.check-box{
    display:flex;
    align-items:center;
    gap:10px;
    background:#f9fafb;
    padding:12px;
    border-radius:10px;
    border:1px solid #e5e7eb;
}
</style>

@endsection