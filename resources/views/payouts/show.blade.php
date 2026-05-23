@extends('layouts.app')

@section('content')

<main class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(219,234,254,.75),_transparent_35%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">{{ $batch->sector_label }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950">{{ $batch->batch_name }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Venue: {{ $batch->venue }}. Fixed payout amount: PHP {{ number_format((float) $batch->payout_amount, 2) }}. Source file: {{ $batch->source_filename }}. Use this workspace during the payout event to search a beneficiary, mark them paid, and attach photo proof.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if($canGenerateReport)
                    <a href="{{ route($reportRouteName, $batch) }}"
                       class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]">
                        Generate Report
                    </a>
                @endif

                <a href="{{ $indexRoute }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Back to Payout Batches
                </a>
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
            <p class="font-semibold">Please review the payout update.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Total Names</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ $summary['total_entries'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700">Pending</p>
            <p class="mt-3 text-3xl font-black text-amber-950">{{ $summary['pending_count'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700">Paid</p>
            <p class="mt-3 text-3xl font-black text-emerald-950">{{ $summary['paid_count'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-rose-700">Absent</p>
            <p class="mt-3 text-3xl font-black text-rose-950">{{ $summary['absent_count'] ?? 0 }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-600">Deferred</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ $summary['deferred_count'] ?? 0 }}</p>
        </article>
    </section>

    <section class="panel-card">
        <form method="GET"
              x-ref="searchForm"
              class="library-filter">
            <div class="min-w-0">
                <input type="text"
                       name="search"
                       class="input payout-filter-control"
                       value="{{ $filters['search'] }}"
                       placeholder="Search beneficiary or reference number"
                       autofocus
                       @keydown.enter.prevent="$refs.searchForm.submit()">
            </div>

            <div class="min-w-0">
                <select name="status" class="input payout-filter-control" @change="$refs.searchForm.submit()">
                    <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                    <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                    <option value="paid" @selected($filters['status'] === 'paid')>Paid</option>
                    <option value="absent" @selected($filters['status'] === 'absent')>Absent</option>
                    <option value="deferred" @selected($filters['status'] === 'deferred')>Deferred</option>
                </select>
            </div>

            <div class="min-w-0">
                <button type="submit" class="btn-secondary payout-filter-button">
                    Filter
                </button>
            </div>
        </form>
    </section>

    <section class="panel-card">
        @if($entries->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                No payout records match the current filters.
            </div>
        @else
            <div class="table-shell">
                <table class="library-table">
                    <thead>
                        <tr>
                            <th>Queue</th>
                            <th>Beneficiary</th>
                            <th>Sector / Amount</th>
                            <th>Status</th>
                            <th>Proof</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    @foreach($entries as $entry)
                        @php
                            $statusTone = match ($entry->payout_status) {
                                'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                'absent' => 'border-rose-200 bg-rose-50 text-rose-800',
                                'deferred' => 'border-slate-300 bg-slate-100 text-slate-700',
                                default => 'border-amber-200 bg-amber-50 text-amber-800',
                            };
                            $lockedByAnotherUser = $entry->isHandlingLockActive() && (int) $entry->handling_by_user_id !== (int) auth()->id();
                            $handlingLabel = $entry->handlingUser?->name ?: 'another user';
                        @endphp
                        <tbody>
                            <tr x-data="{
                                    open: false,
                                    busy: false,
                                    async claimAndOpen() {
                                        if (this.busy) {
                                            return;
                                        }
                                        this.busy = true;
                                        try {
                                            const response = await fetch(@js(route($claimRouteName, [$batch, $entry])), {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': @js(csrf_token()),
                                                    'Accept': 'application/json',
                                                },
                                            });

                                            if (!response.ok) {
                                                const payload = await response.json().catch(() => ({}));
                                                alert(payload.message || 'This record is currently being handled by another user.');
                                                return;
                                            }

                                            this.open = true;
                                        } finally {
                                            this.busy = false;
                                        }
                                    },
                                    async releaseLock() {
                                        if (!this.open) {
                                            return;
                                        }
                                        this.open = false;
                                        await fetch(@js(route($releaseRouteName, [$batch, $entry])), {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': @js(csrf_token()),
                                                'Accept': 'application/json',
                                            },
                                        });
                                    }
                                }">
                                <td>
                                    <p class="table-primary">#{{ $entry->sequence_no }}</p>
                                    @if($entry->reference_no)
                                        <p class="table-secondary">{{ $entry->reference_no }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p class="table-primary">{{ $entry->full_name }}</p>
                                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                                        <p>Subtype: {{ $entry->assistance_subtype ?: 'Not provided' }}</p>
                                        <p>Detail: {{ $entry->assistance_detail ?: 'Not provided' }}</p>
                                        <p>Birthdate: {{ $entry->birthdate?->format('M d, Y') ?? 'Not provided' }}</p>
                                    </div>
                                    @if($entry->remarks)
                                        <p class="table-secondary">Note: {{ $entry->remarks }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p>{{ $entry->sector_label ?: $batch->sector_label }}</p>
                                    <p class="table-secondary !mt-1 font-semibold !text-slate-950">PHP {{ number_format((float) $batch->payout_amount, 2) }}</p>
                                </td>
                                <td>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] {{ $statusTone }}">
                                        {{ strtoupper($entry->payout_status) }}
                                    </span>
                                    @if($entry->paid_at)
                                        <p class="table-secondary !mt-2 font-semibold !text-emerald-700">
                                            Paid on {{ $entry->paid_at->format('M d, Y h:i A') }}
                                        </p>
                                        <p class="table-secondary !mt-1">
                                            Paid by {{ $entry->paidBy?->name ?: 'Unknown staff' }}
                                        </p>
                                    @endif
                                    @if($lockedByAnotherUser)
                                        <p class="table-secondary !mt-2 !text-rose-700">
                                            Currently being handled by {{ $handlingLabel }}
                                        </p>
                                    @elseif($entry->isHandlingLockActive() && (int) $entry->handling_by_user_id === (int) auth()->id())
                                        <p class="table-secondary !mt-2 !text-sky-700">
                                            Currently being handled by you
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    @if($entry->hasProofPhoto())
                                        <a href="{{ route($proofRouteName, [$batch, $entry]) }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ route($proofRouteName, [$batch, $entry]) }}"
                                                 alt="Proof photo for {{ $entry->full_name }}"
                                                 class="h-20 w-20 rounded-2xl border border-slate-200 object-cover shadow-sm">
                                        </a>
                                    @else
                                        <span class="table-secondary !mt-0">No proof yet</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button"
                                            class="payout-action-icon {{ $lockedByAnotherUser ? 'payout-action-icon--locked' : ($entry->payout_status === 'paid' ? 'payout-action-icon--view' : 'payout-action-icon--process') }}"
                                            @click="{{ $lockedByAnotherUser ? '' : 'claimAndOpen()' }}"
                                            @disabled($lockedByAnotherUser)
                                            title="{{ $lockedByAnotherUser ? 'Being handled by '.$handlingLabel : ($entry->payout_status === 'paid' ? 'View payout record' : 'Process payout') }}"
                                            aria-label="{{ $lockedByAnotherUser ? 'Being handled by '.$handlingLabel : ($entry->payout_status === 'paid' ? 'View payout record' : 'Process payout') }}">
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ $lockedByAnotherUser ? 'lock' : ($entry->payout_status === 'paid' ? 'visibility' : 'payments') }}
                                        </span>
                                    </button>
                                    <div x-show="open" x-cloak class="modal-shell modal-shell--inline">
                                        <div class="modal-backdrop" @click="releaseLock()"></div>
                                        <div class="modal-card modal-card--payout"
                                             x-data="{
                                                cameraOpen: false,
                                                stream: null,
                                                photoData: '',
                                                previewUrl: @js($entry->hasProofPhoto() ? route($proofRouteName, [$batch, $entry]) : null),
                                                async startCamera() {
                                                    if (!navigator.mediaDevices?.getUserMedia) {
                                                        alert('Camera access is not available in this browser.');
                                                        return;
                                                    }
                                                    try {
                                                        this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                                                        this.cameraOpen = true;
                                                        this.$nextTick(() => {
                                                            if (this.$refs.video) {
                                                                this.$refs.video.srcObject = this.stream;
                                                                this.$refs.video.play();
                                                            }
                                                        });
                                                    } catch (error) {
                                                        alert('Unable to access the camera. Please allow camera permission and try again.');
                                                    }
                                                },
                                                capturePhoto() {
                                                    if (!this.$refs.video || !this.$refs.canvas) {
                                                        return;
                                                    }
                                                    const video = this.$refs.video;
                                                    const canvas = this.$refs.canvas;
                                                    canvas.width = video.videoWidth || 1280;
                                                    canvas.height = video.videoHeight || 720;
                                                    const ctx = canvas.getContext('2d');
                                                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                                    this.photoData = canvas.toDataURL('image/jpeg', 0.92);
                                                    this.previewUrl = this.photoData;
                                                    this.stopCamera();
                                                    this.cameraOpen = false;
                                                },
                                                stopCamera() {
                                                    if (this.stream) {
                                                        this.stream.getTracks().forEach(track => track.stop());
                                                        this.stream = null;
                                                    }
                                                },
                                                retakePhoto() {
                                                    this.photoData = '';
                                                    this.previewUrl = null;
                                                    this.startCamera();
                                                }
                                             }"
                                             @click.outside="releaseLock(); stopCamera()"
                                             x-on:keydown.escape.window="releaseLock(); stopCamera()">
                                            <div class="modal-head">
                                                <div>
                                                    <p class="panel-kicker">Payout Processing</p>
                                                    <h2 class="panel-title">{{ $entry->full_name }}</h2>
                                                    <p class="table-secondary !mt-2">Queue #{{ $entry->sequence_no }}{{ $entry->reference_no ? ' • '.$entry->reference_no : '' }}</p>
                                                </div>
                                                <button type="button" class="modal-close" @click="releaseLock(); stopCamera()">Close</button>
                                            </div>

                                            <form method="POST"
                                                  action="{{ route($updateRouteName, [$batch, $entry]) }}"
                                                  enctype="multipart/form-data"
                                                  class="modal-form">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-grid two">
                                                    <div>
                                                        <label class="label">Update Status</label>
                                                        <select name="payout_status" class="input">
                                                            <option value="pending" @selected($entry->payout_status === 'pending')>Pending</option>
                                                            <option value="paid" @selected($entry->payout_status === 'paid')>Paid</option>
                                                            <option value="absent" @selected($entry->payout_status === 'absent')>Absent</option>
                                                            <option value="deferred" @selected($entry->payout_status === 'deferred')>Deferred</option>
                                                        </select>
                                                    </div>

                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Fixed Amount</p>
                                                        <p class="mt-2 text-2xl font-black text-slate-950">PHP {{ number_format((float) $batch->payout_amount, 2) }}</p>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="label">Payout Notes</label>
                                                    <textarea name="payout_notes" class="input min-h-[120px]" placeholder="Optional field notes, ID issues, reschedule details, or release remarks.">{{ old('payout_notes', $entry->payout_notes) }}</textarea>
                                                </div>

                                                <div>
                                                    <input type="hidden" name="proof_photo_data" :value="photoData">
                                                    <label class="label">Proof Photo Capture</label>

                                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                        <div class="flex flex-wrap gap-2">
                                                            <button type="button"
                                                                    class="inline-flex items-center rounded-xl bg-[#234E70] px-4 py-2 text-sm font-semibold text-white hover:bg-[#18384f]"
                                                                    @click="startCamera()">
                                                                Open Camera
                                                            </button>
                                                            <button type="button"
                                                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                                                    @click="retakePhoto()"
                                                                    x-show="previewUrl"
                                                                    x-cloak>
                                                                Retake
                                                            </button>
                                                        </div>

                                                        <div class="mt-3" x-show="cameraOpen" x-cloak>
                                                            <video x-ref="video" class="w-full rounded-2xl border border-slate-200 bg-slate-950" playsinline autoplay muted></video>
                                                            <div class="mt-3 flex gap-2">
                                                                <button type="button"
                                                                        class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                                                        @click="capturePhoto()">
                                                                    Capture Photo
                                                                </button>
                                                                <button type="button"
                                                                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                                                        @click="stopCamera(); cameraOpen = false;">
                                                                    Close Camera
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <canvas x-ref="canvas" class="hidden"></canvas>

                                                        <div class="mt-3" x-show="previewUrl" x-cloak>
                                                            <p class="mb-2 text-xs font-black uppercase tracking-[0.18em] text-slate-500">Captured Preview</p>
                                                            <img :src="previewUrl" alt="Captured payout proof" class="w-full rounded-2xl border border-slate-200 object-cover shadow-sm">
                                                        </div>
                                                    </div>

                                                    <p class="mt-2 text-xs text-slate-500">Required when tagging this beneficiary as paid.</p>
                                                </div>

                                                <div class="modal-actions">
                                                    <button type="button" class="btn-secondary" @click="releaseLock(); stopCamera()">Cancel</button>
                                                    <button type="submit" class="btn-primary">Save Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </section>

    @if($entries->hasPages())
        <section class="panel-card !py-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-sm text-slate-500">
                    Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ $entries->total() }} beneficiaries
                </p>
                <div>
                    {{ $entries->links() }}
                </div>
            </div>
        </section>
    @endif
</main>

<style>
.panel-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}

.library-filter{
    display:grid;
    grid-template-columns:minmax(0,1fr) 180px auto;
    gap:12px;
}

.table-shell{
    overflow-x:auto;
    border-radius:18px;
    border:1px solid #e2e8f0;
}

.library-table{
    width:100%;
    border-collapse:collapse;
}

.library-table thead{
    background:#f8fafc;
}

.library-table th,
.library-table td{
    padding:16px 18px;
    text-align:left;
    border-bottom:1px solid #e2e8f0;
    vertical-align:top;
}

.library-table th{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#64748b;
}

.table-primary{
    font-weight:700;
    color:#0f172a;
}

.table-secondary{
    margin-top:6px;
    color:#64748b;
    font-size:13px;
}

.payout-filter-control{
    min-height: 56px;
    width: 100%;
    border-radius: 14px;
    border: 1px solid #d8e1ec;
    background: #ffffff;
    padding: 0 18px;
    font-size: 15px;
    font-weight: 500;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.payout-filter-control::placeholder{
    color: #94a3b8;
}

.payout-filter-button{
    display: inline-flex;
    width: auto;
    min-height: 56px;
    align-items: center;
    justify-content: center;
    padding: 0 22px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
}

.payout-action-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:42px;
    width:42px;
    border-radius:12px;
    box-shadow:0 1px 2px rgba(15, 23, 42, 0.08);
    transition:background-color .2s ease, color .2s ease, transform .2s ease;
}

.payout-action-icon:hover{
    transform:translateY(-1px);
}

.payout-action-icon--process{
    background:#dbeafe;
    color:#1d4ed8;
}

.payout-action-icon--process:hover{
    background:#bfdbfe;
}

.payout-action-icon--view{
    background:#ecfdf5;
    color:#047857;
}

.payout-action-icon--view:hover{
    background:#d1fae5;
}

.payout-action-icon--locked{
    background:#e2e8f0;
    color:#64748b;
    cursor:not-allowed;
}

.btn-primary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:44px;
    padding:0 18px;
    border-radius:12px;
    background:#234E70;
    color:#ffffff;
    font-size:14px;
    font-weight:700;
}

.btn-primary:hover{
    background:#18384f;
}

.modal-shell{
    position:fixed;
    inset:0;
    z-index:60;
}

.modal-shell--inline{
    position:fixed;
    inset:0;
}

.modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15, 23, 42, .52);
}

.modal-card{
    position:relative;
    margin:40px auto;
    width:min(720px, calc(100% - 32px));
    max-height:calc(100vh - 80px);
    overflow:auto;
    border-radius:24px;
    background:#fff;
    box-shadow:0 30px 70px rgba(15, 23, 42, .28);
}

.modal-card--payout{
    width:min(860px, calc(100% - 32px));
}

.modal-head{
    display:flex;
    justify-content:space-between;
    gap:16px;
    padding:24px 24px 18px;
    border-bottom:1px solid #e2e8f0;
}

.modal-close{
    border-radius:999px;
    background:#f1f5f9;
    padding:10px 14px;
    font-weight:700;
    color:#475569;
}

.modal-form{
    padding:24px;
    display:flex;
    flex-direction:column;
    gap:18px;
}

.modal-grid{
    display:grid;
    gap:16px;
}

.modal-grid.two{
    grid-template-columns:repeat(2, minmax(0, 1fr));
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:8px;
}

@media (max-width: 1023px){
    .library-filter{
        grid-template-columns:1fr;
    }

    .payout-filter-control,
    .payout-filter-button{
        min-height: 50px;
        border-radius: 16px;
        font-size: 15px;
    }

    .modal-grid.two{
        grid-template-columns:1fr;
    }
}
</style>

@endsection
