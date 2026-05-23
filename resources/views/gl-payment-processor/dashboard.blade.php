@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(191,219,254,.55),_transparent_28%),linear-gradient(135deg,_#ffffff_0%,_#eef6ff_48%,_#f8fafc_100%)] p-6 shadow-sm lg:p-8">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_340px] xl:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">GL Payment Processor</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-sky-950">Guarantee Letter Dashboard</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Monitor guarantee letter cases only, see which records are still awaiting SOA, and move paid-ready files through processing with a clearer operational overview.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('gl-payment-processor.queue') }}"
                       class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#18384f]">
                        Open GL Queue
                    </a>
                    <a href="{{ route('gl-payment-processor.queue', ['payment_status' => 'awaiting_soa']) }}"
                       class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Check Awaiting SOA
                    </a>
                </div>
            </div>

            <div class="grid gap-3">
                <div class="rounded-3xl border border-slate-200 bg-white/90 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Queue Size</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">Total guarantee letter cases in the processor workspace.</p>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 px-5 py-5 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Paid Completion</p>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <p class="text-3xl font-black text-emerald-950">{{ $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100) : 0 }}%</p>
                        <p class="text-sm font-semibold text-emerald-800">{{ $stats['paid'] }} paid</p>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-emerald-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Please review the submitted details.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-amber-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-amber-700">Awaiting SOA</span>
            <p class="mt-3 text-3xl font-black text-amber-950">{{ $stats['awaiting_soa'] }}</p>
            <p class="mt-2 text-sm text-amber-800">Cases with no submitted statement yet.</p>
        </article>
        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-blue-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-blue-700">For Processing</span>
            <p class="mt-3 text-3xl font-black text-blue-950">{{ $stats['for_processing'] }}</p>
            <p class="mt-2 text-sm text-blue-800">Cases with submitted SOA that are not yet paid.</p>
        </article>
        <article class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-rose-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-rose-700">Returned Cases</span>
            <p class="mt-3 text-3xl font-black text-rose-950">{{ $returnedCases->count() }}</p>
            <p class="mt-2 text-sm text-rose-800">Most recent records sent back for compliance follow-up.</p>
        </article>
        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">Paid</span>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $stats['paid'] }}</p>
            <p class="mt-2 text-sm text-emerald-800">Completed payments already tagged in the queue.</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Returned Queue</p>
                    <h2 class="mt-2 text-2xl font-black text-sky-950">Needs Compliance Follow-up</h2>
                </div>
                <a href="{{ route('gl-payment-processor.queue') }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Open Full Queue
                </a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($returnedCases as $application)
                    <article class="rounded-3xl border border-rose-200 bg-rose-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border border-rose-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-rose-700">
                                        Returned for Compliance
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $application->reference_no }}</span>
                                </div>
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->serviceProvider?->name ?? 'No provider assigned' }}</p>
                            </div>

                            <a href="{{ route('gl-payment-processor.show', $application->id) }}"
                               class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                                Review Case
                            </a>
                        </div>

                        @if($application->gl_soa_review_notes)
                            <div class="mt-4 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-rose-700">
                                <span class="font-semibold">Review notes:</span> {{ $application->gl_soa_review_notes }}
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                        No returned cases need follow-up right now.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Recent Activity</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Latest SOA Submissions</h2>

                <div class="mt-5 space-y-3">
                    @forelse($recentSubmissions as $application)
                        @php
                            $latestStatement = $application->documents
                                ->where('document_type', 'Updated Statement of Account')
                                ->sortByDesc('created_at')
                                ->first();
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-sm font-bold text-slate-900">{{ $application->reference_no }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')) ?: '-' }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-blue-700">
                                    {{ optional($latestStatement?->created_at)->format('M d, Y h:i A') ?? 'Recently submitted' }}
                                </span>
                                <a href="{{ route('gl-payment-processor.show', $application->id) }}" class="text-sm font-semibold text-[#234E70] hover:text-[#18384f]">
                                    Open
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No submitted SOA records yet.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Provider Load</p>
                <h2 class="mt-2 text-2xl font-black text-sky-950">Top Queue Sources</h2>

                <div class="mt-5 space-y-3">
                    @forelse($providerLoad as $provider)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $provider['provider'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $provider['total'] }} total case{{ $provider['total'] === 1 ? '' : 's' }}</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-amber-700">
                                    {{ $provider['awaiting_soa'] }} awaiting SOA
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                            No provider load data available yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    </section>
</main>

@endsection
