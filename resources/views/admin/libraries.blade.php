@extends('layouts.app')

@section('content')

<main class="space-y-6">

    <section class="libraries-hero">
        <div>
            <p class="libraries-kicker">Administrator</p>
            <h1 class="libraries-title">Libraries</h1>
            <p class="libraries-copy">
                Maintain the shared assistance and relationship records used across the platform.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="libraries-back">
            <span class="material-symbols-outlined text-[18px]">dashboard</span>
            Dashboard
        </a>
    </section>

    @if(session('success'))
        <div class="libraries-alert libraries-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="libraries-alert libraries-alert--error">
            <p class="font-semibold">Please review the submitted library values.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.1fr,.9fr]">
        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Libraries</p>
                    <h2 class="panel-title">Add Library Records</h2>
                </div>
            </div>

            <div class="grid gap-4 mt-6 md:grid-cols-2">
                <form method="POST"
                      action="{{ route('admin.libraries.assistance-types.store') }}"
                      class="library-form">
                    @csrf
                    <h3 class="library-form-title">Assistance Type</h3>
                    <label class="label">Type Name</label>
                    <input type="text" name="name" class="input" placeholder="Medical Assistance">
                    <button type="submit" class="btn-primary w-full mt-4">Add Assistance Type</button>
                </form>

                <form method="POST"
                      action="{{ route('admin.libraries.relationships.store') }}"
                      class="library-form">
                    @csrf
                    <h3 class="library-form-title">Relationship</h3>
                    <label class="label">Relationship Name</label>
                    <input type="text" name="name" class="input" placeholder="Sibling">
                    <button type="submit" class="btn-primary w-full mt-4">Add Relationship</button>
                </form>
            </div>

            <form method="POST"
                  action="{{ route('admin.libraries.modes-of-assistance.store') }}"
                  class="library-form mt-4">
                @csrf
                <h3 class="library-form-title">Mode of Assistance</h3>
                <label class="label">Mode Name</label>
                <input type="text" name="name" class="input" placeholder="Guarantee Letter">
                <button type="submit" class="btn-primary w-full mt-4">Add Mode of Assistance</button>
            </form>

            <form method="POST"
                  action="{{ route('admin.libraries.assistance-subtypes.store') }}"
                  class="library-form mt-4">
                @csrf
                <h3 class="library-form-title">Assistance Subtype</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Parent Assistance Type</label>
                        <select name="assistance_type_id" class="input">
                            <option value="">Select type</option>
                            @foreach($assistanceTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Subtype Name</label>
                        <input type="text" name="name" class="input" placeholder="Hospital Bill">
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-4">Add Assistance Subtype</button>
            </form>

            <form method="POST"
                  action="{{ route('admin.libraries.assistance-details.store') }}"
                  class="library-form mt-4">
                @csrf
                <h3 class="library-form-title">Assistance Detail</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Parent Assistance Subtype</label>
                        <select name="assistance_subtype_id" class="input">
                            <option value="">Select subtype</option>
                            @foreach($assistanceTypes as $type)
                                @foreach($type->subtypes as $subtype)
                                    <option value="{{ $subtype->id }}">{{ $type->name }} - {{ $subtype->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Detail Name</label>
                        <input type="text" name="name" class="input" placeholder="Payment for Hospital Bill">
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-4">Add Assistance Detail</button>
            </form>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Reference Data</p>
                    <h2 class="panel-title">Current Library Records</h2>
                </div>
            </div>

            <div class="space-y-4 mt-6">
                @foreach($assistanceTypes as $type)
                    <div class="soft-card">
                        <p class="soft-card-title">{{ $type->name }}</p>
                        <p class="soft-card-copy">
                            @if($type->subtypes->isNotEmpty())
                                @foreach($type->subtypes as $subtype)
                                    <span class="font-semibold">{{ $subtype->name }}</span>{{ $subtype->details->isNotEmpty() ? ': '.$subtype->details->pluck('name')->implode(', ') : '' }}@if(!$loop->last); @endif
                                @endforeach
                            @else
                                No subtypes added yet.
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <p class="panel-kicker">Modes of Assistance</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($modesOfAssistance as $mode)
                        <span class="tag-pill">{{ $mode->name }}</span>
                    @empty
                        <p class="text-sm text-slate-500">No mode records found.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                <p class="panel-kicker">Relationships</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($relationships as $relationship)
                        <span class="tag-pill">{{ $relationship->name }}</span>
                    @empty
                        <p class="text-sm text-slate-500">No relationship records found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

</main>

<style>
.libraries-hero{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:16px;
    padding:28px 30px;
    border-radius:24px;
    background:
        radial-gradient(circle at top right, rgba(149, 204, 170, .32), transparent 30%),
        linear-gradient(135deg, #ffffff 0%, #edf7f2 100%);
    border:1px solid #dcece3;
}
.libraries-kicker,
.panel-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.libraries-title{
    margin-top:10px;
    font-size:34px;
    font-weight:900;
    color:#163750;
}
.libraries-copy{
    margin-top:10px;
    color:#64748b;
    max-width:760px;
}
.libraries-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:11px 16px;
    border-radius:14px;
    background:#234E70;
    color:#fff;
    font-weight:700;
}
.libraries-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.libraries-alert--success{
    background:#ecfdf5;
    color:#166534;
    border-color:#bbf7d0;
}
.libraries-alert--error{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}
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
.panel-title{
    font-size:22px;
    font-weight:900;
    color:#163750;
    margin-top:6px;
}
.soft-card,
.library-form{
    border:1px solid #e2e8f0;
    background:#f8fafc;
    border-radius:18px;
    padding:18px;
}
.soft-card-title,
.library-form-title{
    font-weight:800;
    color:#0f172a;
}
.soft-card-copy{
    margin-top:8px;
    color:#64748b;
    font-size:14px;
    line-height:1.5;
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
}
</style>

@endsection
