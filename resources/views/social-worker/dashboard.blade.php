@extends('layouts.app')

@section('content')

<main class="p-8 space-y-6 max-w-6xl mx-auto">

<!-- HERO -->
<div class="rounded-2xl bg-gradient-to-br from-[#0B3C5D] to-[#174A6B] p-8 text-white shadow">

<span class="text-xs bg-white/20 px-3 py-1 rounded-full uppercase tracking-wide">
Application Hub
</span>

<h1 class="text-3xl font-bold mt-4">
Managing Welfare Services with Precision.
</h1>

<p class="text-sm opacity-80 mt-2 max-w-2xl">
Review, track, and process citizen applications through our centralized crisis response interface.
</p>

</div>

<!-- KPI CARDS -->
<div class="grid grid-cols-5 gap-6">

<!-- TOTAL PENDING -->
<div class="bg-white rounded-xl p-6 shadow flex justify-between items-center">
<div>
<p class="text-sm text-gray-500">Total Pending</p>
<p class="text-2xl font-bold mt-2">{{ $totalPending }}</p>
<p class="text-xs text-gray-400">+12 from last week</p>
</div>

<div class="w-12 h-12 bg-blue-100 text-[#0B3C5D] flex items-center justify-center rounded-lg">
📋
</div>
</div>

<!-- APPROVED TODAY -->
<div class="bg-white rounded-xl p-6 shadow flex justify-between items-center">
<div>
<p class="text-sm text-gray-500">Approved Today</p>
<p class="text-2xl font-bold mt-2">{{ $approvedToday }}</p>
<p class="text-xs text-green-600">High efficiency</p>
</div>

<div class="w-12 h-12 bg-green-100 text-green-600 flex items-center justify-center rounded-lg">
✔
</div>
</div>

<!-- URGENT -->
<div class="bg-white rounded-xl p-6 shadow flex justify-between items-center">
<div>
<p class="text-sm text-gray-500">Urgent Reviews</p>
<p class="text-2xl font-bold mt-2">{{ $urgent }}</p>
<p class="text-xs text-yellow-600">Immediate attention</p>
</div>

<div class="w-12 h-12 bg-yellow-100 text-yellow-600 flex items-center justify-center rounded-lg">
!
</div>
</div>

<!-- TOTAL CASES -->
<div class="bg-white rounded-xl p-6 shadow flex justify-between items-center">
<div>
<p class="text-sm text-gray-500">Cases Managed</p>
<p class="text-2xl font-bold mt-2">{{ $totalHandled }}</p>
<p class="text-xs text-gray-400">Lifetime total</p>
</div>

<div class="w-12 h-12 bg-blue-100 text-[#0B3C5D] flex items-center justify-center rounded-lg">
👤
</div>
</div>

<!-- MY CASES -->
<div class="bg-white rounded-xl p-6 shadow flex justify-between items-center">
<div>
<p class="text-sm text-gray-500">My Assessed Cases</p>
<p class="text-2xl font-bold mt-2">{{ $myHandled }}</p>
<p class="text-xs text-gray-400">Personally handled</p>
</div>

<div class="w-12 h-12 bg-indigo-100 text-indigo-700 flex items-center justify-center rounded-lg">
ðŸ§¾
</div>
</div>

</div>

</main>

@endsection
