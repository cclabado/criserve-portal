@extends('layouts.app')

@section('content')

<main class="space-y-6">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-300 bg-red-100 px-4 py-4 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <p class="panel-kicker">Support Queue</p>
                <h1 class="panel-title">Support Tickets</h1>
                <p class="text-sm text-slate-500 mt-2">
                    Review account-recovery and support submissions from the public support form.
                </p>
            </div>
        </div>

        <form method="GET" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.75fr)_280px_auto_auto] xl:items-end">
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Search Tickets</span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Search by name, email, subject, or message"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                    >
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Status</span>
                    <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-300 focus:ring-2 focus:ring-sky-100">
                        <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="open" {{ $filters['status'] === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $filters['status'] === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $filters['status'] === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $filters['status'] === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </label>

                <button type="submit" class="rounded-xl bg-[#163750] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#123149]">
                    Filter
                </button>

                <a
                    href="{{ route('admin.support-tickets') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Clear
                </a>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        @forelse($tickets as $ticket)
            <article class="panel-card">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="tag-pill">#{{ $ticket->id }}</span>
                            <span class="tag-pill">{{ str_replace('_', ' ', $ticket->status) }}</span>
                            @if($ticket->source)
                                <span class="tag-pill">{{ str_replace('-', ' ', $ticket->source) }}</span>
                            @endif
                        </div>

                        <div>
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $ticket->subject }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $ticket->name }} · {{ $ticket->email }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Submitted {{ $ticket->created_at->format('M d, Y h:i A') }}
                                @if($ticket->ip_address)
                                    · IP {{ $ticket->ip_address }}
                                @endif
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                            {{ $ticket->message }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="min-w-[220px] rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        @csrf
                        @method('PATCH')

                        <label class="text-sm font-semibold text-slate-600">Ticket Status</label>
                        <select name="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-sky-300">
                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>

                        <button type="submit" class="mt-4 w-full rounded-xl bg-[#163750] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123149]">
                            Update Status
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel-card text-sm text-slate-500">
                No support tickets found.
            </div>
        @endforelse
    </section>

    <div class="px-2">
        {{ $tickets->links() }}
    </div>

</main>

<style>
.panel-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
}
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#6b7a89;
}
.panel-title{
    font-size:22px;
    font-weight:900;
    color:#163750;
    margin-top:6px;
}
.tag-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    font-weight:700;
    background:#f1f5f9;
    color:#334155;
    text-transform:capitalize;
}
</style>

@endsection
