@extends('layouts.app')

@section('content')

<main class="p-8 max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-[#234E70]">
                Approval Queue
            </h1>

            <p class="text-gray-500">
                Applications waiting for final decision.
            </p>
        </div>
    </div>

    <!-- SUCCESS -->
    @if(session('success'))
    <div id="successAlert"
         class="px-4 py-3 rounded-lg bg-green-100 text-green-800 border border-green-200">
        {{ session('success') }}
    </div>

    <script>
        setTimeout(() => {
            document.getElementById('successAlert')?.remove();
        }, 3000);
    </script>
    @endif

    <!-- HERO -->
    <div class="rounded-2xl bg-gradient-to-br from-[#234E70] to-[#18384f] p-8 text-white shadow">
        <h2 class="text-2xl font-bold">Pending Approvals</h2>
        <p class="text-sm opacity-80 mt-1">
            Review recommended assistance and make final decisions.
        </p>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-left">

            <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-6 py-4">Applicant</th>
                    <th class="px-6 py-4">Assistance Type</th>
                    <th class="px-6 py-4">Recommended</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">

                @forelse($applications as $app)

                <tr class="hover:bg-gray-50">

                    <!-- Applicant -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">

                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-[#234E70]">
                                {{ strtoupper(substr($app->client->first_name,0,1)) }}
                            </div>

                            <div>
                                <p class="font-semibold text-sm">
                                    {{ $app->client->first_name }} {{ $app->client->last_name }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    Ref: {{ $app->reference_no }}
                                </p>
                            </div>

                        </div>
                    </td>

                    <!-- Type -->
                    <td class="px-6 py-4 text-sm">
                        {{ $app->assistanceType->name ?? '-' }}
                    </td>

                    <!-- Recommended -->
                    <td class="px-6 py-4 text-sm font-semibold text-[#234E70]">
                        ₱{{ number_format($app->recommended_amount ?? 0, 2) }}
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-amber-100 text-amber-700 font-semibold">
                            FOR APPROVAL
                        </span>
                    </td>

                    <!-- Action -->
                    <td class="px-6 py-4 text-right">

                        <a href="{{ route('approving.show', $app->id) }}"
                           class="px-4 py-2 bg-[#234E70] text-white rounded-lg text-sm hover:bg-[#18384f]">
                            Review
                        </a>

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        No pending approvals.
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

        <!-- FOOTER -->
        <div class="px-6 py-4">
            {{ $applications->links() }}
        </div>

    </div>

</main>

@endsection