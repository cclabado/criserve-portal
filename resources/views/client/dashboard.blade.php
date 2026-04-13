@extends('layouts.app')

@section('content')

<div class="p-8 max-w-7xl mx-auto space-y-10 bg-surface">

    <!-- Welcome Section -->
    <section class="relative rounded-xl overflow-hidden bg-gradient-to-br from-primary to-primary-container p-12 text-white shadow-xl">
        <div>
            <h2 class="text-3xl font-bold">Welcome back.</h2>
            <p class="text-sm mt-2">Ready to proceed with your service requests?</p>
            <button class="mt-4 bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold">
                Apply for Assistance
            </button>
        </div>
    </section>

    <!-- Status Tracker -->
    <section class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold text-sky-950">Active Application Status</h3>
                <p class="text-sm text-on-surface-variant">Tracking ID: #APP-2023-0892</p>
            </div>
            <span class="px-4 py-1.5 bg-tertiary-container/10 text-tertiary-fixed-dim rounded-full text-xs font-bold uppercase">
                In Progress
            </span>
        </div>

        <div class="bg-surface-container-lowest p-10 rounded-xl shadow-sm border border-outline-variant/10">

            <div class="flex items-center justify-between relative">

                <!-- LINE -->
                <div class="absolute top-6 left-0 w-full h-1 bg-surface-container-high">
                    <div class="bg-primary h-full w-[60%]"></div>
                </div>

                <!-- STEP -->
                <div class="flex flex-col items-center gap-2 z-10">
                    <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white">
                        ✔
                    </div>
                    <p class="text-sm font-bold text-primary">Submitted</p>
                </div>

                <div class="flex flex-col items-center gap-2 z-10">
                    <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white">
                        ✔
                    </div>
                    <p class="text-sm font-bold text-primary">Under Review</p>
                </div>

                <div class="flex flex-col items-center gap-2 z-10">
                    <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">
                        ●
                    </div>
                    <p class="text-sm font-bold text-on-primary-container">For Interview</p>
                </div>

                <div class="flex flex-col items-center gap-2 z-10 opacity-40">
                    <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center">
                        ○
                    </div>
                    <p class="text-sm font-bold">Approved</p>
                </div>

                <div class="flex flex-col items-center gap-2 z-10 opacity-40">
                    <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center">
                        ○
                    </div>
                    <p class="text-sm font-bold">Released</p>
                </div>

            </div>

        </div>

    </section>

    <!-- Applications Table -->
    <section class="space-y-6">

        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-bold text-sky-950">History & Submissions</h3>

            <div class="flex gap-2">
                <button class="px-4 py-2 text-sm border border-outline-variant rounded-lg hover:bg-surface-container">
                    Export PDF
                </button>
                <button class="px-4 py-2 text-sm bg-surface-container-high rounded-lg">
                    Filter
                </button>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/10 overflow-hidden">

            <table class="w-full text-left border-collapse">

                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            Reference ID
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            Type of Assistance
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            Submission Date
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            Current Status
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-surface-container">

                    <!-- ROW 1 -->
                    <tr class="hover:bg-surface transition-colors">
                        <td class="px-6 py-5">
                            <span class="font-mono text-xs font-bold text-sky-800">
                                #APP-2023-0892
                            </span>
                        </td>

                        <td class="px-6 py-5">
                            <p class="text-sm font-semibold text-on-surface">
                                Emergency Medical Support
                            </p>
                            <p class="text-xs text-on-surface-variant">
                                Hospitalization & Medicines
                            </p>
                        </td>

                        <td class="px-6 py-5 text-sm text-on-surface-variant">
                            Oct 12, 2023
                        </td>

                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-primary-fixed text-on-primary-fixed text-[10px] font-bold rounded-full uppercase">
                                For Interview
                            </span>
                        </td>

                        <td class="px-6 py-5">
                            <button class="text-primary font-bold hover:underline text-sm">
                                View Details
                            </button>
                        </td>
                    </tr>

                    <!-- ROW 2 -->
                    <tr class="hover:bg-surface transition-colors">
                        <td class="px-6 py-5">
                            <span class="font-mono text-xs font-bold text-sky-800">
                                #APP-2023-0541
                            </span>
                        </td>

                        <td class="px-6 py-5">
                            <p class="text-sm font-semibold text-on-surface">
                                Livelihood Assistance
                            </p>
                            <p class="text-xs text-on-surface-variant">
                                Micro-enterprise Startup
                            </p>
                        </td>

                        <td class="px-6 py-5 text-sm text-on-surface-variant">
                            Aug 24, 2023
                        </td>

                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-[10px] font-bold rounded-full uppercase">
                                Released
                            </span>
                        </td>

                        <td class="px-6 py-5">
                            <button class="text-primary font-bold hover:underline text-sm">
                                View Receipt
                            </button>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</div>

@endsection