@extends('layouts.app')

@section('content')

<main class="p-8 space-y-6 max-w-6xl mx-auto">

<!-- HEADER -->
<header class="w-full sticky top-0 bg-white border-b px-8 py-4 flex justify-between">

<div class="flex items-center gap-6 w-full">
<h1 class="text-xl font-bold text-[#0B3C5D]">Applications</h1>

<div class="relative w-full max-w-md">
<form method="GET" action="/social-worker/applications" class="w-full max-w-md">
    <input 
        type="text" 
        name="search"
        value="{{ request('search') }}"
        placeholder="Search case ID or applicant..."
        class="w-full pl-4 pr-4 py-2 rounded-full bg-gray-100 focus:ring-2 focus:ring-[#0B3C5D]"
        onkeypress="if(event.key === 'Enter') this.form.submit();"
    >
</form>
</div>
</div>

</header>

<!-- CONTENT -->
<div class="p-8 space-y-6">

@if(session('success'))
<div id="successAlert"
    class="mb-4 px-4 py-3 rounded-lg bg-green-100 text-green-800 border border-green-200">
    {{ session('success') }}
</div>

<script>
setTimeout(() => {
    document.getElementById('successAlert')?.remove();
}, 3000);
</script>
@endif

<!-- HERO -->
<div class="rounded-2xl bg-gradient-to-br from-[#0B3C5D] to-[#174A6B] p-8 text-white shadow">
<h2 class="text-3xl font-bold mb-2">Application Management</h2>
<p class="text-sm opacity-80">
Review and manage submitted applications efficiently.
</p>
</div>

<!-- FILTER BAR -->
<form method="GET" class="bg-white rounded-xl shadow p-4 flex justify-between items-center flex-wrap gap-4">

<div class="flex gap-4 flex-wrap">

<!-- STATUS -->
<div>
<p class="text-xs text-gray-500 mb-1">STATUS FILTER</p>
<select name="status" class="input">
<option value="all">All Statuses</option>
<option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
<option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Under Review</option>
<option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
<option value="denied" {{ request('status')=='denied'?'selected':'' }}>Denied</option>
</select>
</div>

<!-- TYPE -->
<div>
<p class="text-xs text-gray-500 mb-1">APPLICATION TYPE</p>
<select name="type" class="input">
<option value="all">All Types</option>
@foreach(\App\Models\AssistanceType::all() as $type)
<option value="{{ $type->id }}" {{ request('type')==$type->id?'selected':'' }}>
{{ $type->name }}
</option>
@endforeach
</select>
</div>

<!-- DATE FROM -->
<div>
<p class="text-xs text-gray-500 mb-1">DATE FROM</p>
<input type="date" name="date_from" value="{{ request('date_from') }}" class="input">
</div>

<!-- DATE TO -->
<div>
<p class="text-xs text-gray-500 mb-1">DATE TO</p>
<input type="date" name="date_to" value="{{ request('date_to') }}" class="input">
</div>
<input type="hidden" name="search" value="{{ request('search') }}">
</div>

<div class="flex gap-2">

<a href="/social-worker/applications"
class="px-4 py-2 bg-gray-200 rounded-lg text-sm">
Clear
</a>

<button type="submit"
class="px-4 py-2 bg-[#0B3C5D] text-white rounded-lg text-sm">
Filter
</button>

</div>

</form>

<!-- TABLE -->
<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full text-left">

<thead class="bg-gray-100 text-xs uppercase text-gray-500">
<tr>
<th class="px-6 py-4">Applicant Identity</th>
<th class="px-6 py-4">Service Category</th>
<th class="px-6 py-4">Current Status</th>
<th class="px-6 py-4">Submission Date</th>
<th class="px-6 py-4 text-right">Actions</th>
</tr>
</thead>

<tbody class="divide-y">

@foreach($applications as $app)

<tr class="hover:bg-gray-50 transition">

<!-- APPLICANT -->
<td class="px-6 py-4">
<div class="flex items-center gap-3">

<div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-[#0B3C5D]">
{{ strtoupper(substr($app->client->first_name,0,1)) }}
</div>

<div>
<p class="font-semibold text-sm">
{{ $app->client->first_name }} {{ $app->client->last_name }}
</p>
<p class="text-xs text-gray-500">
ID: {{ $app->reference_no }}
</p>
</div>

</div>
</td>

<!-- CATEGORY -->
<td class="px-6 py-4 text-sm">
{{ $app->assistanceType->name ?? 'N/A' }}
</td>

<!-- STATUS -->
<td class="px-6 py-4">

@php
$status = $app->status;
@endphp

<span class="px-3 py-1 text-xs font-bold rounded-full

@if($status == 'submitted')
bg-yellow-100 text-yellow-700

@elseif($status == 'under_review')
bg-blue-100 text-blue-700

@elseif($status == 'approved')
bg-green-100 text-green-700

@elseif($status == 'denied')
bg-red-100 text-red-700

@elseif($status == 'released')
        bg-emerald-100 text-emerald-700
        
@else
bg-gray-100 text-gray-600
@endif

">
{{ strtoupper(str_replace('_',' ',$status)) }}
</span>

</td>

<!-- DATE -->
<td class="px-6 py-4 text-sm text-gray-500">
{{ $app->created_at->format('M d, Y') }}
</td>

<!-- ACTION -->
<td class="px-6 py-4 text-sm">

    @php $status = $app->status; @endphp

    <div class="flex justify-end items-center h-full">

        @if($status === 'submitted')

            <a href="/social-worker/application/{{ $app->id }}/assess"
            class="px-4 py-2 w-28 text-center rounded-lg text-sm font-semibold 
                   bg-amber-500 text-white hover:bg-amber-600 shadow-sm hover:shadow-md transition">
                Assess
            </a>

        @elseif($status === 'under_review')

            <a href="{{ route('socialworker.intake', $app->id) }}"
            class="px-4 py-2 w-28 text-center rounded-lg text-sm font-semibold 
                   bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-md transition">
                Intake
            </a>

        @elseif($status === 'for_approval')

            <a href="/social-worker/application/{{ $app->id }}"
            class="px-4 py-2 w-28 text-center rounded-lg text-sm font-semibold 
                   border border-slate-300 text-slate-700 hover:bg-slate-100 shadow-sm transition">
                View
            </a>

        @elseif($status === 'approved')

            <div class="flex justify-end gap-2">

                <a href="{{ route('socialworker.show', $app->id) }}"
                class="px-4 py-2 rounded-lg text-sm font-semibold border border-slate-300 text-slate-700 hover:bg-slate-100">
                    View Details
                </a>

                <a href="{{ route('socialworker.certificate', $app->id) }}"
                target="_blank"
                class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700">
                    Print Certificate
                </a>

                <form method="POST"
                    action="{{ route('socialworker.release', $app->id) }}">
                    @csrf

                    <button type="submit"
                        onclick="return confirm('Mark this application as released?')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                        Mark Released
                    </button>
                </form>

            </div>

        @else

            <a href="/social-worker/application/{{ $app->id }}"
            class="px-4 py-2 w-28 text-center rounded-lg text-sm font-semibold 
                   bg-gray-200 text-gray-700">
                View
            </a>

        @endif

    </div>

</td>

</tr>

@endforeach

</tbody>

</table>

<!-- FOOTER -->
<div class="flex justify-between items-center px-6 py-4 text-sm text-gray-500">

<p>
Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }} applications
</p>

<div class="mt-4">
    {{ $applications->links() }}
</div>

</div>

</div>


</main>

@endsection