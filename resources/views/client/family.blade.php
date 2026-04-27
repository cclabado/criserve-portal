@extends('layouts.app')

@section('content')
@php
    $familyMembers = old('family_last_name')
        ? collect(old('family_last_name'))->map(function ($lastName, $index) {
            return [
                'id' => old("family_id.$index"),
                'last_name' => $lastName,
                'first_name' => old("family_first_name.$index"),
                'middle_name' => old("family_middle_name.$index"),
                'extension_name' => old("family_extension_name.$index"),
                'relationship' => old("family_relationship.$index"),
                'birthdate' => old("family_birthdate.$index"),
            ];
        })->values()
        : $client->familyMembers->map(function ($member) {
            $possibleMatch = $possibleAccountMatches[$member->id] ?? null;

            return [
                'id' => $member->id,
                'last_name' => $member->last_name,
                'first_name' => $member->first_name,
                'middle_name' => $member->middle_name,
                'extension_name' => $member->extension_name,
                'relationship' => $member->relationship,
                'birthdate' => optional($member->birthdate)->format('Y-m-d') ?? $member->birthdate,
                'linked_user_id' => $member->linked_user_id,
                'linked_account_email' => $member->linkedUser?->email,
                'possible_account_match' => (bool) $possibleMatch,
                'possible_birthdate_match' => (bool) ($possibleMatch['birthdate_matches'] ?? false),
            ];
        })->values();
@endphp

@php
    $familyNetworkNodes = collect($familyNetwork['nodes'] ?? []);
    $familyNetworkAnchor = $familyNetwork['anchor'] ?? null;
    $familyNetworkEdges = collect($familyNetwork['edges'] ?? []);
    $anchorId = $familyNetworkAnchor['id'] ?? null;

    $familyNetworkTiers = [
        'parents' => collect(),
        'siblings' => collect(),
        'children' => collect(),
        'relatives' => collect(),
    ];

    if ($anchorId) {
        foreach ($familyNetworkEdges as $edge) {
            if (($edge['from'] ?? null) !== $anchorId) {
                continue;
            }

            $node = $familyNetworkNodes->firstWhere('id', $edge['to'] ?? null);
            if (! $node) {
                continue;
            }

            $label = strtolower(trim((string) ($edge['label'] ?? '')));

            if (in_array($label, ['mother', 'father', 'guardian', 'grandmother', 'grandfather', 'grandparent'], true)) {
                $familyNetworkTiers['parents']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            if (in_array($label, ['son', 'daughter', 'child', 'grandchild', 'grandson', 'granddaughter'], true)) {
                $familyNetworkTiers['children']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            if (in_array($label, ['sibling', 'brother', 'sister', 'stepchild', 'stepsibling', 'half-brother', 'half-sister'], true)) {
                $familyNetworkTiers['siblings']->push([...$node, 'edge_label' => $edge['label']]);
                continue;
            }

            $familyNetworkTiers['relatives']->push([...$node, 'edge_label' => $edge['label']]);
        }
    }
@endphp

<div class="mx-auto max-w-7xl space-y-8 p-8" x-data="familyModule(@js($familyMembers), @js($suggestions))" x-init="init()">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#11405f] via-[#1f5b80] to-[#4b89ab] p-10 text-white shadow-xl">
        <div class="relative z-10 max-w-3xl">
            <span class="inline-flex items-center rounded-full bg-white/15 px-4 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                Client Household
            </span>
            <h1 class="mt-4 text-4xl font-black tracking-tight">My Family</h1>
            <p class="mt-3 text-sm text-white/85 sm:text-base">
                Maintain your household information here so future assistance applications can load the same family composition automatically.
            </p>
        </div>
        <div class="pointer-events-none absolute -right-12 -top-8 h-44 w-44 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 right-20 h-24 w-24 rounded-full bg-cyan-100/25 blur-2xl"></div>
    </section>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
            <p class="font-semibold">Please complete the required family fields.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-sky-950">Household Snapshot</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        This module stores your saved family composition separately from any single application.
                    </p>
                </div>

                @if($familyNetworkAnchor)
                    <button type="button" @click="showNetworkModal = true" class="inline-flex items-center justify-center rounded-2xl bg-slate-100 px-5 py-3 text-sm font-bold text-sky-800 transition hover:bg-slate-200">
                        View Family Network
                    </button>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 px-5 py-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Saved Members</p>
                    <p class="mt-2 text-3xl font-black text-sky-950" x-text="familyRows.length"></p>
                </div>

                <div class="rounded-2xl bg-slate-50 px-5 py-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Detected Suggestions</p>
                    <p class="mt-2 text-3xl font-black text-sky-950" x-text="suggestions.length"></p>
                </div>

                <div class="rounded-2xl bg-slate-50 px-5 py-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Account Holder</p>
                    <p class="mt-2 text-lg font-bold text-sky-950">
                        {{ $client->first_name }} {{ $client->last_name }}
                    </p>
                    <p class="text-sm text-slate-500">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>
        </div>

        <div x-show="suggestions.length > 0" class="rounded-3xl border border-sky-200 bg-sky-50/60 p-6 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-sky-950">Detected Family Members</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        These were detected from already linked family-member accounts. Review them first, then add only the ones that belong in your household.
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-sky-800 shadow-sm">
                    Review Required
                </span>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.key">
                    <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-lg font-black text-sky-950" x-text="formatMemberName(suggestion)"></p>
                                <p class="mt-1 text-sm text-slate-600">
                                    Suggested relationship:
                                    <span class="font-semibold text-sky-900" x-text="relationshipName(suggestion.relationship)"></span>
                                </p>
                            </div>
                            <span x-show="suggestion.linked_user_id" class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                Linked Account
                            </span>
                            <span x-show="!suggestion.linked_user_id" class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">
                                Detected from Linked Household
                            </span>
                        </div>

                        <div class="mt-4 space-y-1 text-sm text-slate-500">
                            <p>
                                Detected from:
                                <span class="font-semibold text-slate-700" x-text="suggestion.detected_from || 'Linked family member'"></span>
                            </p>
                            <p>
                                Source household:
                                <span class="font-semibold text-slate-700" x-text="suggestion.source_account_holder || 'Linked account'"></span>
                            </p>
                            <p>
                                Birthdate:
                                <span class="font-semibold text-slate-700" x-text="formatDate(suggestion.birthdate)"></span>
                            </p>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="button" @click="openSuggestionModal(index)" class="inline-flex items-center rounded-2xl bg-[#123a58] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f314b]">
                                Add to My Family
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-sky-950">Family Composition</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Add, remove, or update family members tied to your client account.
                    </p>
                </div>

                <button type="button" @click="openCreateModal()" class="inline-flex items-center justify-center rounded-2xl bg-sky-100 px-5 py-3 text-sm font-bold text-sky-800 transition hover:bg-sky-200">
                    + Add Family Member
                </button>
            </div>

            <form method="POST" action="{{ route('client.family.update') }}" class="mt-6 space-y-4">
                @csrf
                <template x-for="member in familyRows" :key="member.key">
                    <div>
                        <input type="hidden" name="family_id[]" :value="member.id">
                        <input type="hidden" name="family_last_name[]" :value="member.last_name">
                        <input type="hidden" name="family_first_name[]" :value="member.first_name">
                        <input type="hidden" name="family_middle_name[]" :value="member.middle_name">
                        <input type="hidden" name="family_extension_name[]" :value="member.extension_name">
                        <input type="hidden" name="family_relationship[]" :value="member.relationship">
                        <input type="hidden" name="family_birthdate[]" :value="member.birthdate">
                    </div>
                </template>

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Full Name</th>
                                    <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Relationship</th>
                                    <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Date of Birth</th>
                                    <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Account Link</th>
                                    <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <template x-if="familyRows.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No family members saved yet. Use <span class="font-semibold text-sky-800">Add Family Member</span> to start your household list.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="(member, index) in familyRows" :key="member.key">
                                    <tr class="align-top transition hover:bg-slate-50">
                                        <td class="px-5 py-4">
                                            <p class="text-base font-black leading-snug text-sky-950" x-text="formatMemberName(member)"></p>
                                            <p class="mt-1 text-xs font-medium uppercase tracking-[0.16em] text-slate-400" x-show="member.extension_name">
                                                <span>Extension: </span><span x-text="member.extension_name"></span>
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-700" x-text="relationshipName(member.relationship)"></td>
                                        <td class="px-5 py-4 text-sm text-slate-700" x-text="formatDate(member.birthdate)"></td>
                                        <td class="px-5 py-4">
                                            <template x-if="member.linked_user_id">
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                    Linked Account
                                                </span>
                                            </template>
                                            <template x-if="!member.linked_user_id && member.possible_account_match">
                                                <div class="space-y-2">
                                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                        Possible Match
                                                    </span>
                                                    <p class="max-w-[15rem] text-xs text-amber-700" x-text="member.possible_birthdate_match
                                                        ? 'Existing client account looks similar, but automatic linking still needs review.'
                                                        : 'Existing client account found, but the birthdate does not match yet.'">
                                                    </p>
                                                </div>
                                            </template>
                                            <template x-if="!member.linked_user_id">
                                                <span x-show="!member.possible_account_match" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                                    Not Linked
                                                </span>
                                            </template>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" @click="openEditModal(index)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 transition hover:bg-sky-200 hover:text-sky-900" title="Edit family member" aria-label="Edit family member">
                                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                                </button>
                                                <button type="button" @click="removeRow(index)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition hover:bg-rose-200 hover:text-rose-700" title="Remove family member" aria-label="Remove family member">
                                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Updates here will be available the next time you file an application.
                    </p>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[#123a58] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#0f314b]">
                        Save Family Composition
                    </button>
                </div>
            </form>
        </div>
    </section>

    <div x-cloak x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-4 py-8">
        <div @click.outside="closeModal()" class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500" x-text="modalMode === 'suggestion' ? 'Detected Suggestion' : (editingIndex === null ? 'New Entry' : 'Update Entry')"></p>
                    <h3 class="mt-1 text-2xl font-black text-sky-950" x-text="modalMode === 'suggestion' ? 'Confirm Family Relationship' : (editingIndex === null ? 'Add Family Member' : 'Edit Family Member')"></h3>
                </div>
                <button type="button" @click="closeModal()" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-200">
                    Close
                </button>
            </div>

            <div class="space-y-5 px-6 py-6">
                <template x-if="modalMode === 'suggestion'">
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        We detected this person from a linked household. Please confirm the relationship before adding them to your family composition.
                    </div>
                </template>

                <div class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Last Name</label>
                        <input x-model="draftMember.last_name" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">First Name</label>
                        <input x-model="draftMember.first_name" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Middle Name</label>
                        <input x-model="draftMember.middle_name" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Extension</label>
                        <input x-model="draftMember.extension_name" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Relationship</label>
                        <div class="select-shell mt-2">
                            <select x-model="draftMember.relationship" class="form-select !bg-white !py-2.5 !text-sm">
                                <option value="">Select</option>
                                @foreach ($relationships as $relationship)
                                    <option value="{{ $relationship->id }}">{{ $relationship->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Date of Birth</label>
                        <input type="date" x-model="draftMember.birthdate" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                    </div>
                </div>

                <template x-if="modalError">
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" x-text="modalError"></div>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" @click="closeModal()" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                    Cancel
                </button>
                <button type="button" @click="saveModalMember()" class="rounded-2xl bg-[#123a58] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#0f314b]">
                    Save Member
                </button>
            </div>
        </div>
    </div>

    @if($familyNetworkAnchor)
    <div x-cloak x-show="showNetworkModal" x-transition.opacity class="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/70 backdrop-blur-[2px]">
        <div class="flex min-h-full items-center justify-center px-3 py-3 sm:px-4 sm:py-4">
        <div @click.outside="showNetworkModal = false" class="flex w-full max-w-4xl flex-col overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6 sm:py-5">
                <div>
                    <h3 class="text-2xl font-black text-sky-950 sm:text-3xl">Family Network</h3>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500">
                        Identity-aware family tree based on your saved household composition and linked client accounts.
                    </p>
                </div>
                <button type="button" @click="showNetworkModal = false" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-200">
                    Close
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
            <div class="space-y-6 sm:space-y-8">
                @if($familyNetworkTiers['parents']->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($familyNetworkTiers['parents'] as $node)
                            <div class="rounded-3xl border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm sm:px-5 sm:py-5">
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                <p class="mt-3 text-xl font-black leading-tight text-sky-950 sm:text-2xl">{{ $node['name'] }}</p>
                                <p class="mt-2 text-sm text-slate-500">Birthdate: {{ \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') }}</p>
                                @if($node['has_account'])
                                    <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-center">
                    <div class="w-full max-w-md rounded-3xl border border-sky-200 bg-gradient-to-br from-white to-sky-50 px-5 py-5 text-center shadow-sm sm:px-6 sm:py-6">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($familyNetworkAnchor['role_display'] ?? $familyNetworkAnchor['role'] ?? 'HOUSEHOLD ROOT') }}</p>
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-sky-700">
                                {{ $familyNetworkAnchor['role'] ?? 'Client Household Root' }}
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-black leading-tight text-sky-950 sm:text-3xl">{{ $familyNetworkAnchor['name'] }}</p>
                        <p class="mt-2 text-sm text-slate-500">Birthdate: {{ \Illuminate\Support\Carbon::parse($familyNetworkAnchor['birthdate'])->format('M d, Y') }}</p>
                    </div>
                </div>

                @if($familyNetworkTiers['siblings']->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($familyNetworkTiers['siblings'] as $node)
                            <div class="rounded-3xl border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm sm:px-5 sm:py-5">
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                <p class="mt-3 text-xl font-black leading-tight text-sky-950 sm:text-2xl">{{ $node['name'] }}</p>
                                <p class="mt-2 text-sm text-slate-500">Birthdate: {{ \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') }}</p>
                                @if($node['has_account'])
                                    <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($familyNetworkTiers['children']->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($familyNetworkTiers['children'] as $node)
                            <div class="rounded-3xl border border-sky-100 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-4 shadow-sm sm:px-5 sm:py-5">
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                <p class="mt-3 text-xl font-black leading-tight text-sky-950 sm:text-2xl">{{ $node['name'] }}</p>
                                <p class="mt-2 text-sm text-slate-500">Birthdate: {{ \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') }}</p>
                                @if($node['has_account'])
                                    <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($familyNetworkTiers['relatives']->isNotEmpty())
                    <div class="space-y-3 border-t border-slate-200 pt-5">
                        <h4 class="text-sm font-black uppercase tracking-[0.2em] text-slate-500">Other Connected Relatives</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($familyNetworkTiers['relatives'] as $node)
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 sm:px-5 sm:py-5">
                                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">{{ strtoupper($node['edge_label']) }}</p>
                                    <p class="mt-3 text-lg font-black leading-tight text-sky-950 sm:text-xl">{{ $node['name'] }}</p>
                                    <p class="mt-2 text-sm text-slate-500">Birthdate: {{ \Illuminate\Support\Carbon::parse($node['birthdate'])->format('M d, Y') }}</p>
                                    @if($node['has_account'])
                                        <span class="mt-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Linked Account</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.select-shell{
    position:relative;
}
.select-shell::after{
    content:'';
    position:absolute;
    right:14px;
    top:50%;
    width:10px;
    height:10px;
    border-right:2px solid #64748b;
    border-bottom:2px solid #64748b;
    transform:translateY(-70%) rotate(45deg);
    pointer-events:none;
}
.form-select{
    width:100%;
    appearance:none;
    border:1px solid #cbd5e1;
    border-radius:0.9rem;
    background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding:0.8rem 2.9rem 0.8rem 0.95rem;
    font-size:0.95rem;
    color:#0f172a;
    transition:border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
}
.form-select:focus{
    outline:none;
    border-color:#234E70;
    box-shadow:0 0 0 4px rgba(35,78,112,.12);
    background:#fff;
}
[x-cloak]{
    display:none !important;
}
</style>

<script>
function familyModule(initialRows, initialSuggestions) {
    return {
        nextKey: 0,
        familyRows: [],
        suggestions: [],
        showModal: false,
        showNetworkModal: false,
        editingIndex: null,
        suggestionIndex: null,
        modalMode: 'manual',
        modalError: '',
        draftMember: null,
        relationships: @js($relationships->mapWithKeys(fn ($relationship) => [(string) $relationship->id => $relationship->name])),

        init() {
            const rows = Array.isArray(initialRows) ? initialRows : [];
            this.familyRows = rows.length ? rows.map((row) => this.normalizeRow(row)) : [];
            const suggestions = Array.isArray(initialSuggestions) ? initialSuggestions : [];
            this.suggestions = suggestions
                .map((row) => this.normalizeRow(row))
                .filter((row) => !this.hasExistingSignature(row));
            this.draftMember = this.emptyRow();
        },

        normalizeRow(row) {
            return {
                key: this.nextKey++,
                id: row.id ?? '',
                last_name: row.last_name ?? '',
                first_name: row.first_name ?? '',
                middle_name: row.middle_name ?? '',
                extension_name: row.extension_name ?? '',
                relationship: row.relationship ? String(row.relationship) : '',
                birthdate: row.birthdate ?? '',
                linked_user_id: row.linked_user_id ?? '',
                linked_account_email: row.linked_account_email ?? '',
                possible_account_match: !!row.possible_account_match,
                possible_birthdate_match: !!row.possible_birthdate_match,
                detected_from: row.detected_from ?? '',
                source_account_holder: row.source_account_holder ?? '',
            };
        },

        emptyRow() {
            return {
                key: this.nextKey++,
                id: '',
                last_name: '',
                first_name: '',
                middle_name: '',
                extension_name: '',
                relationship: '',
                birthdate: '',
                linked_user_id: '',
                linked_account_email: '',
                possible_account_match: false,
                possible_birthdate_match: false,
                detected_from: '',
                source_account_holder: '',
            };
        },

        relationshipName(value) {
            return this.relationships[String(value)] ?? 'Not set';
        },

        formatMemberName(member) {
            const parts = [
                member.first_name,
                member.middle_name,
                member.last_name,
            ].filter((part) => String(part || '').trim() !== '');

            if (parts.length === 0) {
                return 'Unnamed family member';
            }

            return parts.join(' ');
        },

        formatDate(value) {
            if (!value) {
                return 'Not set';
            }

            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) {
                return value;
            }

            return parsed.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        },

        openCreateModal() {
            this.modalMode = 'manual';
            this.editingIndex = null;
            this.suggestionIndex = null;
            this.modalError = '';
            this.draftMember = this.emptyRow();
            this.showModal = true;
        },

        openEditModal(index) {
            this.modalMode = 'manual';
            this.editingIndex = index;
            this.suggestionIndex = null;
            this.modalError = '';
            this.draftMember = { ...this.familyRows[index] };
            this.showModal = true;
        },

        openSuggestionModal(index) {
            const suggestion = this.suggestions[index];
            if (!suggestion) {
                return;
            }

            if (this.hasExistingSignature(suggestion)) {
                this.suggestions.splice(index, 1);
                return;
            }

            this.modalMode = 'suggestion';
            this.editingIndex = null;
            this.suggestionIndex = index;
            this.modalError = '';
            this.draftMember = { ...suggestion };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.modalMode = 'manual';
            this.editingIndex = null;
            this.suggestionIndex = null;
            this.modalError = '';
        },

        saveModalMember() {
            const required = [
                this.draftMember.last_name,
                this.draftMember.first_name,
                this.draftMember.relationship,
                this.draftMember.birthdate,
            ];

            if (required.some((value) => String(value || '').trim() === '')) {
                this.modalError = 'Complete the last name, first name, relationship, and birthdate before saving this member.';
                return;
            }

            if (this.editingIndex === null) {
                this.familyRows.push({ ...this.draftMember, key: this.draftMember.key ?? this.nextKey++ });
            } else {
                this.familyRows.splice(this.editingIndex, 1, { ...this.draftMember });
            }

            if (this.modalMode === 'suggestion' && this.suggestionIndex !== null) {
                this.suggestions.splice(this.suggestionIndex, 1);
            }

            this.closeModal();
        },

        hasExistingSignature(member) {
            const signature = this.memberSignature(member);

            return this.familyRows.some((row) => this.memberSignature(row) === signature);
        },

        memberSignature(member) {
            return [
                member.last_name,
                member.first_name,
                member.middle_name,
                member.extension_name,
                member.birthdate,
            ].map((value) => String(value || '').trim().toLowerCase()).join('|');
        },

        removeRow(index) {
            this.familyRows.splice(index, 1);
        },
    };
}
</script>
@endsection
