@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <div>
        <a href="{{ ($readOnly ?? false) ? route('approving.my-approvals') : route('approving.applications') }}"
           class="text-sm text-gray-500 hover:text-[#234E70]">
            &larr; Back to {{ ($readOnly ?? false) ? 'My Approvals' : 'Approvals' }}
        </a>

        <h1 class="mt-2 text-3xl font-bold text-[#234E70]">
            Final Review
        </h1>

        <p class="text-gray-500">
            Reference: {{ $application->reference_no }}
        </p>
    </div>

    <div class="card">
        <h2 class="title">Client Information</h2>

        <div class="grid grid-cols-4 gap-4 text-sm">
            <div>
                <span class="muted">Name</span><br>
                {{ $application->client->last_name }},
                {{ $application->client->first_name }}
            </div>

            <div>
                <span class="muted">Sex</span><br>
                {{ $application->client->sex }}
            </div>

            <div>
                <span class="muted">Birthdate</span><br>
                {{ $application->client->birthdate }}
            </div>

            <div>
                <span class="muted">Contact</span><br>
                {{ $application->client->contact_number }}
            </div>
        </div>

        <div class="mt-4 text-sm">
            <span class="muted">Address</span><br>
            {{ $application->client->full_address }}
        </div>
    </div>

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
                    {{ $application->modeOfAssistance->name ?? $application->mode_of_assistance }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Final Amount</p>
                <p class="text-2xl font-bold text-[#234E70]">
                    PHP {{ number_format($application->final_amount ?? $application->recommended_amount ?? 0, 2) }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Current Status</p>
                <p class="font-semibold
                    @if($application->status === 'approved') text-emerald-600
                    @elseif($application->status === 'denied') text-red-600
                    @elseif($application->status === 'released') text-sky-600
                    @else text-amber-600
                    @endif">
                    {{ strtoupper(str_replace('_', ' ', $application->status)) }}
                </p>
            </div>
        </div>
    </div>

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
        </div>
    </div>

    <div class="card">
        <h2 class="title">Attachments</h2>

        <div class="space-y-3">
            @forelse($application->documents as $doc)
                <div class="flex items-center justify-between rounded-xl bg-gray-50 px-5 py-3">
                    <div>
                        <p class="text-sm font-semibold">
                            {{ $doc->file_name ?? $doc->filename }}
                        </p>

                        <p class="text-xs text-gray-500">
                            {{ $doc->remarks }}
                        </p>
                    </div>

                    <a href="{{ route('documents.show', $doc->id) }}"
                       class="rounded-lg bg-[#234E70] px-4 py-2 text-sm text-white">
                        View Attachment
                    </a>
                </div>
            @empty
                <p class="text-sm text-gray-500">No attachments.</p>
            @endforelse
        </div>
    </div>

    @if(!($readOnly ?? false) && $application->status === 'for_approval')
        <div class="card">
            <h2 class="title">Final Decision</h2>

            <div class="grid grid-cols-2 gap-6 items-end">
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
                            class="w-full rounded-lg bg-green-600 px-5 py-3 text-white hover:bg-green-700">
                        Approve Application
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('approving.deny', $application->id) }}"
                      class="space-y-3">
                    @csrf

                    <div>
                        <label class="label">Reason for Denial</label>

                        <textarea name="denial_reason"
                                  class="input h-24 w-full"
                                  placeholder="Enter denial reason..."></textarea>
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-red-600 px-5 py-3 text-white hover:bg-red-700">
                        Deny Application
                    </button>
                </form>
            </div>
        </div>
    @endif

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
