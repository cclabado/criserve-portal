@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div>
        <a href="{{ route('approving.applications') }}"
           class="text-sm text-gray-500 hover:text-[#234E70]">
            ← Back to Approvals
        </a>

        <h1 class="text-3xl font-bold text-[#234E70] mt-2">
            Final Review
        </h1>

        <p class="text-gray-500">
            Reference: {{ $application->reference_no }}
        </p>
    </div>

    <!-- CLIENT -->
    <div class="card">
        <h2 class="title">Client Information</h2>

        <div class="grid grid-cols-4 gap-4 text-sm">
            <div><span class="muted">Name</span><br>
                {{ $application->client->last_name }},
                {{ $application->client->first_name }}
            </div>

            <div><span class="muted">Sex</span><br>
                {{ $application->client->sex }}
            </div>

            <div><span class="muted">Birthdate</span><br>
                {{ $application->client->birthdate }}
            </div>

            <div><span class="muted">Contact</span><br>
                {{ $application->client->contact_number }}
            </div>
        </div>

        <div class="mt-4 text-sm">
            <span class="muted">Address</span><br>
            {{ $application->client->full_address }}
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="card">
        <h2 class="title">Case Summary</h2>

        <div class="grid grid-cols-2 gap-6">

            <div>
                <p class="text-sm text-gray-500">Assistance Type</p>
                <p class="font-semibold">
                    {{ $application->assistanceType->name ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Mode</p>
                <p class="font-semibold">
                    {{ $application->mode_of_assistance }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Recommended Amount</p>
                <p class="text-2xl font-bold text-[#234E70]">
                    ₱{{ number_format($application->recommended_amount ?? 0, 2) }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Current Status</p>
                <p class="font-semibold text-amber-600">
                    FOR APPROVAL
                </p>
            </div>

        </div>
    </div>

    <!-- NOTES -->
    <div class="card">
        <h2 class="title">Assessment Notes</h2>

        <div class="space-y-4 text-sm">

            <div>
                <span class="muted">Problem Statement</span><br>
                {{ $application->problem_statement ?: '-' }}
            </div>

            <div>
                <span class="muted">Social Worker Assessment</span><br>
                {{ $application->social_worker_assessment ?: '-' }}
            </div>

            <div>
                <span class="muted">Initial Notes</span><br>
                {{ $application->notes ?: '-' }}
            </div>

        </div>
    </div>

    <!-- ATTACHMENTS -->
    <div class="card">
        <h2 class="title">Attachments</h2>

        <div class="space-y-3">

            @forelse($application->documents as $doc)

            <div class="bg-gray-50 rounded-xl px-5 py-3 flex justify-between items-center">

                <div>
                    <p class="font-semibold text-sm">
                        {{ $doc->file_name ?? $doc->filename }}
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $doc->remarks }}
                    </p>
                </div>

                <a href="{{ asset('storage/' . ($doc->file_path ?? $doc->path)) }}"
                   target="_blank"
                   class="px-4 py-2 bg-[#234E70] text-white rounded-lg text-sm">
                    View File
                </a>

            </div>

            @empty
            <p class="text-gray-500 text-sm">No attachments.</p>
            @endforelse

        </div>
    </div>

    <!-- FINAL DECISION -->
    <div class="card">

        <h2 class="title">Final Decision</h2>

        <div class="grid grid-cols-2 gap-6 items-end">

            <!-- APPROVE -->
            <form method="POST"
                  action="{{ route('approving.approve', $application->id) }}"
                  class="space-y-3">

                @csrf

                <div>
                    <label class="label">Final Approved Amount</label>

                    <input type="number"
                           step="0.01"
                           name="final_amount"
                           class="input w-full"
                           value="{{ $application->final_amount ?? $application->recommended_amount }}">
                </div>

                <button type="submit"
                        class="w-full px-5 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Approve Application
                </button>

            </form>

            <!-- DENY -->
            <form method="POST"
                  action="{{ route('approving.deny', $application->id) }}"
                  class="space-y-3">

                @csrf

                <div>
                    <label class="label">Reason for Denial</label>

                    <textarea name="denial_reason"
                              class="input w-full h-24"
                              placeholder="Enter denial reason..."></textarea>
                </div>

                <button type="submit"
                        class="w-full px-5 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Deny Application
                </button>

            </form>

        </div>

    </div>

</main>

<style>
.card{
    background:white;
    padding:24px;
    border-radius:16px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
}
.title{
    font-size:18px;
    font-weight:700;
    color:#234E70;
    margin-bottom:16px;
}
.muted{
    color:#6b7280;
    font-size:12px;
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
</style>

@endsection