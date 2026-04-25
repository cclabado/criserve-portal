@extends('layouts.app')

@section('content')

<main class="max-w-7xl mx-auto py-8 space-y-6">

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <a href="javascript:history.back()" class="text-sm text-slate-500 hover:text-sky-800">
            &larr; Back
        </a>

        <h1 class="mt-3 text-3xl font-black text-sky-950">Attachment Viewer</h1>
        <p class="mt-2 text-sm text-slate-500">
            Review uploaded requirements securely inside the portal.
        </p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('documents.stream', $document) }}"
           target="_blank"
           class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Open Raw File
        </a>

        <a href="{{ route('documents.download', $document) }}"
           class="inline-flex items-center rounded-xl bg-sky-900 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">
            Download
        </a>
    </div>
</div>

<section class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
    <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Document Info</p>

        <div class="mt-5 space-y-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">File Name</p>
                <p class="mt-2 break-all text-sm font-semibold text-slate-800">{{ $document->file_name }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">File Type</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $mimeType }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Reference No</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $document->application->reference_no ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Applicant</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">
                    {{ $document->application->client->first_name ?? '' }} {{ $document->application->client->last_name ?? '' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Assistance Type</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $document->application->assistanceType->name ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Remarks</p>
                <p class="mt-2 text-sm text-slate-600">{{ $document->remarks ?: 'No remarks added.' }}</p>
            </div>
        </div>
    </aside>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm min-h-[70vh]">
        @if($isInlinePreview)
            <iframe
                src="{{ route('documents.stream', $document) }}"
                class="h-[70vh] w-full rounded-2xl border border-slate-200 bg-slate-50"
                title="Attachment Preview">
            </iframe>
        @else
            <div class="flex h-[70vh] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                <div>
                    <p class="text-lg font-semibold text-slate-800">Preview unavailable for this file type.</p>
                    <p class="mt-2 text-sm text-slate-500">Use the buttons above to open or download the attachment.</p>
                </div>
            </div>
        @endif
    </section>
</section>

</main>

@endsection
