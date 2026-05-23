@extends('layouts.app')

@section('content')

<main x-data="{}" class="space-y-6">

    <section class="dedupe-hero">
        <div>
            <p class="dedupe-kicker">{{ $isReportingOfficer ? 'Reporting Officer' : 'Administrator' }}</p>
            <h1 class="dedupe-title">Bulk Deduplication Workspace</h1>
            <p class="dedupe-copy">
                Upload an Excel or CSV file, compare the rows against the client and beneficiary databases,
                surface likely duplicate findings, and keep only eligible rows in the clean output.
            </p>
        </div>

        <a href="{{ $dashboardRoute }}" class="dedupe-back">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Dashboard
        </a>
    </section>

    @if(session('success'))
        <div class="dedupe-alert dedupe-alert--success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="dedupe-alert dedupe-alert--error">
            <p class="font-semibold">Please review the upload.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="action-grid">
        <article class="upload-spotlight">
            <div>
                <p class="panel-kicker">New Run</p>
                <h2 class="panel-title">Launch Deduplication Upload</h2>
                <p class="panel-copy">Start a fresh comparison without occupying the full workspace. Use the modal to upload the file, choose the comparison source, and apply frequency rules when needed.</p>
            </div>

            <div class="upload-spotlight__actions">
                <button type="button"
                        class="btn-primary"
                        x-on:click="$dispatch('open-modal', 'dedupe-upload-modal')">
                    Upload New Run
                </button>
                <p class="text-xs text-slate-500">Excel and CSV files supported.</p>
            </div>
        </article>

        <article class="guidance-card">
            <p class="panel-kicker">Template Guide</p>
            <h2 class="panel-title">Prepare the Sheet</h2>
            <p class="panel-copy">
                Required columns: `last_name`, `first_name`, `birthdate`.
                Optional: `middle_name`, `extension_name`, `assistance_subtype` or `assistance_subtype_id`,
                `assistance_detail` or `assistance_detail_id`, `frequency_subject`, `frequency_case_key`,
                `reference_no`, `remarks`.
            </p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[240px_minmax(0,1fr)]">
        <div class="panel-card history-panel">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">History</p>
                    <h2 class="panel-title">Recent Runs</h2>
                </div>
            </div>

            <div class="mt-5 space-y-2 history-panel__list">
                @forelse($runs as $run)
                    <a href="{{ route($showRoute, $run, false) }}" class="run-card">
                        <div class="run-card__top">
                            <p class="run-card__title">Run #{{ $run->id }}</p>
                            <span class="run-status-chip run-status-chip--{{ $run->status }}">{{ \Illuminate\Support\Str::headline($run->status) }}</span>
                        </div>
                        <p class="run-card__meta">{{ $run->original_filename }}</p>
                        <p class="run-card__meta">{{ $run->created_at?->format('M d, Y h:i A') }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No deduplication runs yet.</p>
                @endforelse
            </div>
        </div>

    </section>

</main>

<x-modal name="dedupe-upload-modal" :show="$errors->any()" max-width="2xl" focusable>
    <div class="dedupe-modal" x-data="{
        compareSource: @js(old('compare_source', 'system')),
        isUploading: false,
        uploadProgress: 0,
        uploadStatus: '',
        submitUpload() {
            if (this.isUploading) {
                return;
            }

            const form = this.$refs.uploadForm;
            const action = form.getAttribute('action');
            const method = form.getAttribute('method') || 'POST';
            const xhr = new XMLHttpRequest();

            this.isUploading = true;
            this.uploadProgress = 0;
            this.uploadStatus = 'Uploading file...';

            xhr.open(method.toUpperCase(), action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'text/html,application/xhtml+xml');

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }

                this.uploadProgress = Math.min(100, Math.round((event.loaded / event.total) * 100));
                this.uploadStatus = this.uploadProgress >= 100
                    ? 'Upload complete. Processing deduplication...'
                    : 'Uploading file...';
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 400) {
                    this.uploadProgress = 100;
                    this.uploadStatus = 'Upload complete. Processing deduplication...';
                    window.location.href = xhr.responseURL || action;
                    return;
                }

                this.isUploading = false;
                this.uploadStatus = 'Upload failed. Please try again.';
                form.submit();
            });

            xhr.addEventListener('error', () => {
                this.isUploading = false;
                this.uploadStatus = 'Upload failed. Please try again.';
                form.submit();
            });

            xhr.send(new FormData(form));
        },
    }">
        <div class="dedupe-modal__header">
            <div>
                <p class="panel-kicker">Upload</p>
                <h2 class="panel-title">Run Deduplication</h2>
                <p class="panel-copy">Upload the working list and configure how it should be compared before the background processor starts.</p>
            </div>

            <button type="button"
                    class="modal-close"
                    x-on:click="$dispatch('close-modal', 'dedupe-upload-modal')">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form
            method="POST"
            action="{{ $uploadRoute }}"
            enctype="multipart/form-data"
            class="mt-6 space-y-5"
            x-ref="uploadForm"
            @submit.prevent="submitUpload()"
        >
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Spreadsheet File</label>
                    <input type="file" name="spreadsheet" class="input" accept=".xlsx,.xls,.csv" required>
                </div>

                <div>
                    <label class="label">Compare Against</label>
                    <select name="compare_source" class="input" x-model="compareSource">
                        <option value="system" @selected(old('compare_source', 'system') === 'system')>System database</option>
                        <option value="uploaded_list" @selected(old('compare_source') === 'uploaded_list')>Another uploaded list</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div x-show="compareSource === 'uploaded_list'" x-cloak>
                    <label class="label">Reference Comparison List</label>
                    <input type="file" name="reference_spreadsheet" class="input" accept=".xlsx,.xls,.csv">
                    <p class="helper-copy">Upload this only if you choose `Another uploaded list` as the comparison source.</p>
                </div>

                <div class="toggle-card">
                    <label class="toggle-row">
                        <input type="checkbox" name="apply_frequency_rules" value="1" @checked(old('apply_frequency_rules'))>
                        <span>
                            <span class="toggle-title">Apply frequency rules</span>
                            <span class="toggle-copy">Turn this on if the uploaded sheet includes assistance subtype/detail fields and you want only frequency-eligible rows to remain in the clean list.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="dedupe-modal__footer">
                <button type="button"
                        class="btn-secondary"
                        x-on:click="$dispatch('close-modal', 'dedupe-upload-modal')"
                        :disabled="isUploading">
                    Cancel
                </button>

                <button type="submit" class="btn-primary" :disabled="isUploading" :class="{ 'btn-primary--disabled': isUploading }">
                    Upload and Deduplicate
                </button>
            </div>

            <div x-show="isUploading" x-cloak class="upload-progress-card">
                <div class="upload-progress-head">
                    <p class="upload-progress-title">Upload Progress</p>
                    <p class="upload-progress-percent" x-text="`${uploadProgress}%`"></p>
                </div>
                <div class="upload-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="uploadProgress" :aria-valuetext="`${uploadProgress}%`">
                    <div class="upload-progress-fill" :style="`width: ${uploadProgress}%`"></div>
                </div>
                <p class="upload-progress-copy" x-text="uploadStatus"></p>
            </div>
        </form>
    </div>
</x-modal>

<style>
.dedupe-hero,.panel-card,.metric-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
.dedupe-hero{
    padding:28px 30px;
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-end;
    background:
        radial-gradient(circle at top left, rgba(184, 220, 244, .55), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #edf5fb 100%);
}
.dedupe-kicker,.panel-kicker,.metric-label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.dedupe-title,.panel-title{
    color:#163750;
    font-weight:900;
}
.dedupe-title{
    font-size:34px;
    margin-top:10px;
}
.dedupe-copy,.panel-copy{
    margin-top:10px;
    color:#64748b;
}
.dedupe-back,.btn-primary,.btn-secondary{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 16px;
    border-radius:14px;
    font-weight:700;
}
.dedupe-back,.btn-primary{
    background:#234E70;
    color:#fff;
}
.btn-secondary{
    background:#e6eef5;
    color:#234E70;
}
.btn-secondary--disabled{
    opacity:.75;
    cursor:default;
}
.btn-primary--disabled{
    opacity:.7;
    cursor:not-allowed;
}
.panel-card,.metric-card{ padding:22px; }
.metric-value{
    margin-top:10px;
    font-size:30px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
}
.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
}
.input{
    appearance:none;
    border:1px solid #d4dde6;
    border-radius:14px;
    padding:12px 14px;
    width:100%;
    background:#fff;
}
.dedupe-alert{
    border-radius:16px;
    padding:16px 18px;
    border:1px solid transparent;
}
.dedupe-alert--success{ background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
.dedupe-alert--error{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.action-grid{
    display:grid;
    gap:24px;
    grid-template-columns:minmax(0, 1.3fr) minmax(320px, .7fr);
}
.upload-spotlight,.guidance-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
    padding:24px;
}
.upload-spotlight{
    display:flex;
    gap:20px;
    align-items:flex-end;
    justify-content:space-between;
    background:
        radial-gradient(circle at top right, rgba(191, 219, 254, .45), transparent 30%),
        linear-gradient(135deg, #ffffff 0%, #f8fbfe 100%);
}
.upload-spotlight__actions{
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:10px;
    flex-shrink:0;
    min-width:220px;
}
.upload-spotlight__actions .btn-primary{
    width:100%;
    min-width:220px;
}
.table-wrap{ overflow:auto; }
.history-panel{
    padding:18px;
}
.history-panel__list{
    max-height:520px;
    overflow:auto;
    padding-right:4px;
}
.data-table{
    width:100%;
    border-collapse:collapse;
}
.data-table th,.data-table td{
    padding:14px 10px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
    font-size:14px;
}
.data-table th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#64748b;
}
.run-card{
    display:block;
    border:1px solid #d9e6f0;
    border-radius:16px;
    padding:11px 12px;
    color:#163750;
    background:#f8fbfe;
}
.run-card__top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
}
.run-meta-chip{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:8px 12px;
    background:#f8fafc;
}
.helper-copy,.toggle-copy{
    margin-top:8px;
    font-size:12px;
    color:#64748b;
}
.toggle-card{
    border:1px solid #d9e6f0;
    border-radius:18px;
    padding:16px;
    background:#f8fbfe;
}
.toggle-row{
    display:flex;
    gap:12px;
    align-items:flex-start;
}
.toggle-row input{
    margin-top:4px;
}
.toggle-title{
    display:block;
    font-weight:800;
    color:#163750;
}
.upload-progress-card{
    border:1px solid #d9e6f0;
    border-radius:18px;
    padding:16px;
    background:#f8fbfe;
}
.upload-progress-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.upload-progress-title,.upload-progress-percent{
    font-weight:800;
    color:#163750;
}
.upload-progress-track{
    margin-top:12px;
    height:12px;
    border-radius:999px;
    overflow:hidden;
    background:#dbe7f1;
}
.upload-progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #234E70 0%, #3f83b1 100%);
    transition:width .2s ease;
}
.upload-progress-copy{
    margin-top:10px;
    font-size:13px;
    color:#64748b;
}
.run-progress-card{
    border:1px solid #d9e6f0;
    border-radius:18px;
    padding:16px;
    background:#f8fbfe;
}
.run-progress-error{
    margin-top:10px;
    font-size:13px;
    color:#991b1b;
}
.run-status-chip{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.04em;
}
.run-status-chip--queued{
    background:#e0f2fe;
    color:#075985;
}
.run-status-chip--processing{
    background:#dbeafe;
    color:#1d4ed8;
}
.run-status-chip--completed{
    background:#dcfce7;
    color:#166534;
}
.run-status-chip--failed{
    background:#fee2e2;
    color:#991b1b;
}
.run-card--active{
    border-color:#234E70;
    background:#eaf3fb;
}
.run-card__title{
    font-weight:800;
    font-size:13px;
}
.run-card__meta{
    margin-top:4px;
    font-size:11px;
    color:#64748b;
}
.dedupe-modal{
    padding:24px;
}
.dedupe-modal__header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
}
.dedupe-modal__footer{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    flex-wrap:wrap;
}
.modal-close{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:40px;
    height:40px;
    border-radius:999px;
    background:#f8fafc;
    color:#475569;
}
.match-chip{
    margin-bottom:6px;
    padding:8px 10px;
    border-radius:999px;
    background:#f8fafc;
    font-size:12px;
    color:#475569;
}
@media (max-width: 960px){
    .dedupe-hero,.panel-head,.upload-spotlight,.dedupe-modal__header{
        flex-direction:column;
        align-items:flex-start;
    }
    .action-grid{
        grid-template-columns:1fr;
    }
    .upload-spotlight__actions{
        align-items:flex-start;
        width:100%;
        min-width:0;
    }
    .upload-spotlight__actions .btn-primary{
        min-width:0;
    }
}
</style>

@endsection
