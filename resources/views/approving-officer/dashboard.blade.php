@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div>
        <h1 class="text-3xl font-bold text-[#234E70]">
            Approval Dashboard
        </h1>

        <p class="text-gray-500">
            Review and decide pending assistance requests.
        </p>
    </div>

    <!-- HERO -->
    <div class="rounded-2xl bg-gradient-to-br from-[#234E70] to-[#18384f] p-8 text-white shadow">
        <h2 class="text-2xl font-bold">Welcome, Approving Officer</h2>
        <p class="text-sm opacity-80 mt-1">
            Monitor pending approvals and completed decisions.
        </p>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-3 gap-6">

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-sm text-gray-500">Pending Approval</p>
            <h2 class="text-3xl font-bold text-amber-500 mt-2">
                {{ $pending }}
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-sm text-gray-500">Approved Today</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2">
                {{ $approvedToday }}
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-sm text-gray-500">Denied Today</p>
            <h2 class="text-3xl font-bold text-red-600 mt-2">
                {{ $deniedToday }}
            </h2>
        </div>

    </div>

    <!-- QUICK ACTION -->
    <div class="bg-white rounded-xl shadow p-6 flex justify-between items-center">

        <div>
            <h3 class="text-lg font-bold text-[#234E70]">
                Ready for Review
            </h3>

            <p class="text-sm text-gray-500">
                Open the approvals list and process pending requests.
            </p>
        </div>

        <a href="{{ route('approving.applications') }}"
           class="px-5 py-3 bg-[#234E70] text-white rounded-lg hover:bg-[#18384f]">
            View Approvals
        </a>

    </div>

</main>

@endsection