<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPayoutBatchImport;
use App\Models\BulkDeduplicationRun;
use App\Models\PayoutBatch;
use App\Models\PayoutEntry;
use App\Services\AuditLogService;
use App\Services\PayoutImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutImportService $imports,
        protected AuditLogService $auditLogs
    ) {
    }

    public function index(Request $request): View
    {
        $role = (string) $request->user()->role;
        $canCreateBatches = in_array($role, ['admin', 'reporting_officer'], true);
        $canToggleActivation = $role === 'admin';

        $batches = $this->visibleBatchesQuery($request)
            ->withCount('entries')
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $statsQuery = $this->visibleBatchesQuery($request);

        return view('payouts.index', [
            'batches' => $batches,
            'stats' => [
                'total_batches' => (clone $statsQuery)->count(),
                'scheduled_batches' => (clone $statsQuery)->whereDate('payout_date', '>=', now()->toDateString())->count(),
                'today_batches' => (clone $statsQuery)->whereDate('payout_date', now()->toDateString())->count(),
            ],
            'completedRuns' => $this->visibleRunsQuery($request)
                ->where('status', 'completed')
                ->latest()
                ->take(12)
                ->get(),
            'indexRoute' => $this->indexRouteName($request),
            'storeRoute' => $this->storeRouteName($request),
            'showRoute' => $this->showRouteName($request),
            'activationRouteName' => $this->activationRouteName($request),
            'dashboardRoute' => $this->dashboardUrl($request),
            'isReportingOfficer' => $role === 'reporting_officer',
            'canCreateBatches' => $canCreateBatches,
            'canToggleActivation' => $canToggleActivation,
            'canGenerateReport' => in_array($role, ['admin', 'reporting_officer'], true),
            'reportRouteName' => $this->reportRouteName($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_name' => ['required', 'string', 'max:255'],
            'sector_label' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'payout_amount' => ['required', 'numeric', 'min:0.01'],
            'payout_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'bulk_deduplication_run_id' => ['nullable', 'integer'],
            'spreadsheet' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        if (filled($validated['bulk_deduplication_run_id'] ?? null)) {
            $runExists = $this->visibleRunsQuery($request)
                ->whereKey($validated['bulk_deduplication_run_id'])
                ->where('status', 'completed')
                ->exists();

            if (! $runExists) {
                return back()
                    ->withErrors(['bulk_deduplication_run_id' => 'Select a completed deduplication run you can access.'])
                    ->withInput();
            }
        }

        $batch = $this->imports->queueBatch($validated['spreadsheet'], $request->user(), $validated);
        ProcessPayoutBatchImport::dispatch($batch->id);

        $this->auditLogs->log($request, 'payout.batch_created', $batch, [
            'sector_label' => $batch->sector_label,
            'venue' => $batch->venue,
            'payout_amount' => $batch->payout_amount,
            'import_status' => $batch->import_status,
        ]);

        return redirect()
            ->route($this->showRouteName($request), $batch)
            ->with('success', 'Payout batch upload accepted. The clean list is now being imported in the background.');
    }

    public function updateActivation(Request $request, PayoutBatch $batch): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        $batch->update([
            'is_active' => $isActive,
            'activated_by_user_id' => $isActive ? $request->user()->id : null,
            'activated_at' => $isActive ? now() : null,
        ]);

        $this->auditLogs->log($request, 'payout.batch_activation_updated', $batch, [
            'is_active' => $isActive,
        ]);

        return back()->with('success', $isActive
            ? 'Payout batch activated. Internal staff can now access it from their accounts.'
            : 'Payout batch deactivated. It is no longer visible to other internal staff.');
    }

    public function show(Request $request, PayoutBatch $batch): View
    {
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);

        $batch->load(['user', 'bulkDeduplicationRun']);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', 'all'),
        ];

        if ($batch->isImportComplete()) {
            $entries = $batch->entries()
                ->with(['paidBy', 'handlingUser'])
                ->when($filters['search'] !== '', function ($query) use ($filters) {
                    $search = $filters['search'];
                    $query->where(function ($inner) use ($search) {
                        $inner->where('full_name', 'like', "%{$search}%")
                            ->orWhere('reference_no', 'like', "%{$search}%")
                            ->orWhere('sector_label', 'like', "%{$search}%");
                    });
                })
                ->when($filters['status'] !== 'all', fn ($query) => $query->where('payout_status', $filters['status']))
                ->orderBy('sequence_no')
                ->paginate(20)
                ->withQueryString();
        } else {
            $entries = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);
        }

        return view('payouts.show', [
            'batch' => $batch,
            'entries' => $entries,
            'filters' => $filters,
            'summary' => $batch->summary ?? $this->imports->recomputeSummary($batch),
            'indexRoute' => route($this->indexRouteName($request)),
            'updateRouteName' => $this->entryUpdateRouteName($request),
            'proofRouteName' => $this->entryProofRouteName($request),
            'claimRouteName' => $this->entryClaimRouteName($request),
            'releaseRouteName' => $this->entryReleaseRouteName($request),
            'canGenerateReport' => in_array((string) $request->user()->role, ['admin', 'reporting_officer'], true),
            'reportRouteName' => $this->reportRouteName($request),
        ]);
    }

    public function exportReport(Request $request, PayoutBatch $batch): StreamedResponse
    {
        abort_unless(in_array((string) $request->user()->role, ['admin', 'reporting_officer'], true), 403);
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);
        abort_unless($batch->isImportComplete(), 409, 'The payout report becomes available after the batch import completes.');

        $batch->load(['user', 'activatedBy']);
        $summary = $batch->summary ?? $this->imports->recomputeSummary($batch);

        $metadataRows = [
            ['Payout Batch', $batch->batch_name],
            ['Sector', $batch->sector_label],
            ['Venue', $batch->venue],
            ['Payout Date', $batch->payout_date?->format('Y-m-d') ?? ''],
            ['Fixed Amount', (float) $batch->payout_amount],
            ['Source File', $batch->source_filename],
            ['Created By', $batch->user?->name ?? ''],
            ['Activated', $batch->is_active ? 'Yes' : 'No'],
            ['Activated By', $batch->activatedBy?->name ?? ''],
            ['Activated At', $batch->activated_at?->format('Y-m-d H:i:s') ?? ''],
            ['Total Entries', $summary['total_entries'] ?? 0],
            ['Pending', $summary['pending_count'] ?? 0],
            ['Paid', $summary['paid_count'] ?? 0],
            ['Absent', $summary['absent_count'] ?? 0],
            ['Deferred', $summary['deferred_count'] ?? 0],
            ['Notes', $batch->notes ?? ''],
        ];

        $headers = [
            'Queue No',
            'Reference No',
            'Full Name',
            'Birthdate',
            'Sector',
            'Assistance Subtype',
            'Assistance Detail',
            'Payout Status',
            'Paid On',
            'Paid By',
            'Currently Handled By',
            'Handling Started At',
            'Has Proof Photo',
            'Payout Notes',
            'Imported Remarks',
        ];

        $filename = 'payout-report-'.Str::slug($batch->batch_name).'-'.$batch->id.'.csv';

        return response()->streamDownload(function () use ($batch, $metadataRows, $headers) {
            $handle = fopen('php://output', 'w');

            foreach ($metadataRows as [$label, $value]) {
                fputcsv($handle, [$label, $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, $headers);

            $batch->entries()
                ->with(['paidBy:id,name', 'handlingUser:id,name'])
                ->orderBy('id')
                ->chunkById(500, function ($entries) use ($handle, $batch) {
                    foreach ($entries as $entry) {
                        fputcsv($handle, [
                            $entry->sequence_no,
                            $entry->reference_no,
                            $entry->full_name,
                            $entry->birthdate?->format('Y-m-d') ?? '',
                            $entry->sector_label ?: $batch->sector_label,
                            $entry->assistance_subtype,
                            $entry->assistance_detail,
                            $entry->payout_status,
                            $entry->paid_at?->format('Y-m-d H:i:s') ?? '',
                            $entry->paidBy?->name ?? '',
                            $entry->handlingUser?->name ?? '',
                            $entry->handling_started_at?->format('Y-m-d H:i:s') ?? '',
                            $entry->hasProofPhoto() ? 'Yes' : 'No',
                            $entry->payout_notes,
                            $entry->remarks,
                        ]);
                    }
                }, 'id');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function claimEntry(Request $request, PayoutBatch $batch, PayoutEntry $entry): JsonResponse
    {
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);
        abort_unless((int) $entry->payout_batch_id === (int) $batch->id, 404);
        abort_unless($batch->isImportComplete(), 409);

        $entry->refresh();

        if ($this->entryLockedByAnotherUser($entry, $request->user()->id)) {
            return response()->json([
                'message' => 'This payout record is currently being handled by '.$this->handlingUserLabel($entry).'.',
            ], 423);
        }

        $entry->update([
            'handling_by_user_id' => $request->user()->id,
            'handling_started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout record locked for processing.',
        ]);
    }

    public function releaseEntry(Request $request, PayoutBatch $batch, PayoutEntry $entry): JsonResponse
    {
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);
        abort_unless((int) $entry->payout_batch_id === (int) $batch->id, 404);
        abort_unless($batch->isImportComplete(), 409);

        $entry->refresh();

        if ((int) $entry->handling_by_user_id === (int) $request->user()->id) {
            $entry->update([
                'handling_by_user_id' => null,
                'handling_started_at' => null,
            ]);
        }

        return response()->json([
            'message' => 'Payout record released.',
        ]);
    }

    public function updateEntry(Request $request, PayoutBatch $batch, PayoutEntry $entry): RedirectResponse
    {
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);
        abort_unless((int) $entry->payout_batch_id === (int) $batch->id, 404);
        abort_unless($batch->isImportComplete(), 409);

        $entry->loadMissing('handlingUser');

        if ($this->entryLockedByAnotherUser($entry, $request->user()->id)) {
            return back()->withErrors([
                'payout_status' => 'This payout record is currently being handled by '.$this->handlingUserLabel($entry).'.',
            ]);
        }

        if ((int) $entry->handling_by_user_id !== (int) $request->user()->id) {
            $entry->update([
                'handling_by_user_id' => $request->user()->id,
                'handling_started_at' => now(),
            ]);
        }

        $validated = $request->validate([
            'payout_status' => ['required', 'in:pending,paid,absent,deferred'],
            'payout_notes' => ['nullable', 'string'],
            'proof_photo' => ['nullable', 'image', 'max:6144'],
            'proof_photo_data' => ['nullable', 'string'],
        ]);

        $hasCameraCapture = filled($validated['proof_photo_data'] ?? null);

        if ($validated['payout_status'] === 'paid' && ! $entry->hasProofPhoto() && ! $request->hasFile('proof_photo') && ! $hasCameraCapture) {
            return back()->withErrors([
                'proof_photo_data' => 'A proof photo is required before marking this beneficiary as paid.',
            ]);
        }

        $proofPhotoDisk = $entry->proof_photo_disk ?: (string) config('security.payout_proofs.disk', 'local');
        $proofPhotoPath = $entry->proof_photo_path;
        $proofPhotoMimeType = $entry->proof_photo_mime_type;

        if ($request->hasFile('proof_photo')) {
            if ($entry->hasProofPhoto() && Storage::disk($entry->proof_photo_disk)->exists($entry->proof_photo_path)) {
                Storage::disk($entry->proof_photo_disk)->delete($entry->proof_photo_path);
            }

            $proofPhotoDisk = (string) config('security.payout_proofs.disk', 'local');
            $proofPhotoPath = $request->file('proof_photo')->store('payout-proofs', $proofPhotoDisk);
            $proofPhotoMimeType = $request->file('proof_photo')->getMimeType();
        }

        if ($hasCameraCapture) {
            if (! preg_match('/^data:image\/(?P<type>png|jpe?g|webp);base64,(?P<data>.+)$/i', (string) $validated['proof_photo_data'], $matches)) {
                return back()->withErrors([
                    'proof_photo_data' => 'The captured proof photo is invalid. Please capture it again.',
                ]);
            }

            $binary = base64_decode(str_replace(' ', '+', $matches['data']), true);

            if ($binary === false) {
                return back()->withErrors([
                    'proof_photo_data' => 'The captured proof photo could not be decoded. Please capture it again.',
                ]);
            }

            if ($entry->hasProofPhoto() && Storage::disk($entry->proof_photo_disk)->exists($entry->proof_photo_path)) {
                Storage::disk($entry->proof_photo_disk)->delete($entry->proof_photo_path);
            }

            $extension = strtolower($matches['type']) === 'jpeg' ? 'jpg' : strtolower($matches['type']);
            $proofPhotoDisk = (string) config('security.payout_proofs.disk', 'local');
            $proofPhotoPath = 'payout-proofs/'.Str::uuid().'.'.$extension;
            Storage::disk($proofPhotoDisk)->put($proofPhotoPath, $binary);
            $proofPhotoMimeType = 'image/'.($extension === 'jpg' ? 'jpeg' : $extension);
        }

        $entry->update([
            'payout_status' => $validated['payout_status'],
            'payout_notes' => filled($validated['payout_notes'] ?? null) ? trim((string) $validated['payout_notes']) : null,
            'paid_at' => $validated['payout_status'] === 'paid' ? now() : null,
            'paid_by_user_id' => $validated['payout_status'] === 'paid' ? $request->user()->id : null,
            'handling_by_user_id' => null,
            'handling_started_at' => null,
            'proof_photo_disk' => $validated['payout_status'] === 'pending' ? null : $proofPhotoDisk,
            'proof_photo_path' => $validated['payout_status'] === 'pending' ? null : $proofPhotoPath,
            'proof_photo_mime_type' => $validated['payout_status'] === 'pending' ? null : $proofPhotoMimeType,
        ]);

        $batch->update([
            'summary' => $this->imports->recomputeSummary($batch),
        ]);

        $this->auditLogs->log($request, 'payout.entry_updated', $entry, [
            'batch_id' => $batch->id,
            'payout_status' => $validated['payout_status'],
        ]);

        return redirect()
            ->route($this->showRouteName($request), $batch)
            ->with('success', 'Payout record updated.');
    }

    public function proofPhoto(Request $request, PayoutBatch $batch, PayoutEntry $entry): StreamedResponse
    {
        abort_unless($this->visibleBatchesQuery($request)->whereKey($batch->id)->exists(), 403);
        abort_unless((int) $entry->payout_batch_id === (int) $batch->id, 404);
        abort_unless($batch->isImportComplete(), 409);
        abort_unless($entry->hasProofPhoto(), 404);

        return Storage::disk($entry->proof_photo_disk)->response(
            $entry->proof_photo_path,
            basename($entry->proof_photo_path),
            [
                'Content-Type' => $entry->proof_photo_mime_type ?: 'image/jpeg',
            ]
        );
    }

    protected function visibleBatchesQuery(Request $request)
    {
        $role = (string) $request->user()->role;
        $query = PayoutBatch::query();

        if ($role === 'reporting_officer') {
            $query->where(function ($builder) use ($request) {
                $builder->where('is_active', true)
                    ->orWhere('user_id', $request->user()->id);
            });
        }

        if ($role !== 'admin' && $role !== 'reporting_officer') {
            $query->where('is_active', true);
        }

        return $query;
    }

    protected function visibleRunsQuery(Request $request)
    {
        $query = BulkDeduplicationRun::query();

        if ($request->user()->role === 'reporting_officer') {
            $query->where('user_id', $request->user()->id);
        }

        return $query;
    }

    protected function indexRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.index',
            'social_worker' => 'socialworker.payouts.index',
            'approving_officer' => 'approving.payouts.index',
            'referral_officer' => 'referral-officer.payouts.index',
            'gl_payment_processor' => 'gl-payment-processor.payouts.index',
            'technical_staff' => 'technical-staff.payouts.index',
            'admin_staff' => 'admin-staff.payouts.index',
            default => 'admin.payouts.index',
        };
    }

    protected function storeRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.store',
            default => 'admin.payouts.store',
        };
    }

    protected function showRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.show',
            'social_worker' => 'socialworker.payouts.show',
            'approving_officer' => 'approving.payouts.show',
            'referral_officer' => 'referral-officer.payouts.show',
            'gl_payment_processor' => 'gl-payment-processor.payouts.show',
            'technical_staff' => 'technical-staff.payouts.show',
            'admin_staff' => 'admin-staff.payouts.show',
            default => 'admin.payouts.show',
        };
    }

    protected function entryUpdateRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.entries.update',
            'social_worker' => 'socialworker.payouts.entries.update',
            'approving_officer' => 'approving.payouts.entries.update',
            'referral_officer' => 'referral-officer.payouts.entries.update',
            'gl_payment_processor' => 'gl-payment-processor.payouts.entries.update',
            'technical_staff' => 'technical-staff.payouts.entries.update',
            'admin_staff' => 'admin-staff.payouts.entries.update',
            default => 'admin.payouts.entries.update',
        };
    }

    protected function entryProofRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.entries.proof',
            'social_worker' => 'socialworker.payouts.entries.proof',
            'approving_officer' => 'approving.payouts.entries.proof',
            'referral_officer' => 'referral-officer.payouts.entries.proof',
            'gl_payment_processor' => 'gl-payment-processor.payouts.entries.proof',
            'technical_staff' => 'technical-staff.payouts.entries.proof',
            'admin_staff' => 'admin-staff.payouts.entries.proof',
            default => 'admin.payouts.entries.proof',
        };
    }

    protected function entryClaimRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.entries.claim',
            'social_worker' => 'socialworker.payouts.entries.claim',
            'approving_officer' => 'approving.payouts.entries.claim',
            'referral_officer' => 'referral-officer.payouts.entries.claim',
            'gl_payment_processor' => 'gl-payment-processor.payouts.entries.claim',
            'technical_staff' => 'technical-staff.payouts.entries.claim',
            'admin_staff' => 'admin-staff.payouts.entries.claim',
            default => 'admin.payouts.entries.claim',
        };
    }

    protected function entryReleaseRouteName(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => 'reporting.payouts.entries.release',
            'social_worker' => 'socialworker.payouts.entries.release',
            'approving_officer' => 'approving.payouts.entries.release',
            'referral_officer' => 'referral-officer.payouts.entries.release',
            'gl_payment_processor' => 'gl-payment-processor.payouts.entries.release',
            'technical_staff' => 'technical-staff.payouts.entries.release',
            'admin_staff' => 'admin-staff.payouts.entries.release',
            default => 'admin.payouts.entries.release',
        };
    }

    protected function activationRouteName(Request $request): string
    {
        return 'admin.payouts.activation.update';
    }

    protected function reportRouteName(Request $request): string
    {
        return (string) $request->user()->role === 'reporting_officer'
            ? 'reporting.payouts.report'
            : 'admin.payouts.report';
    }

    protected function dashboardUrl(Request $request): string
    {
        return match ((string) $request->user()->role) {
            'reporting_officer' => route('reporting.dashboard'),
            'social_worker' => url('/social-worker/dashboard'),
            'approving_officer' => route('approving.dashboard'),
            'referral_officer' => route('referral-officer.dashboard'),
            'gl_payment_processor' => route('gl-payment-processor.dashboard'),
            'technical_staff' => route('technical-staff.payouts.index'),
            'admin_staff' => route('admin-staff.payouts.index'),
            default => route('admin.dashboard'),
        };
    }

    protected function entryLockedByAnotherUser(PayoutEntry $entry, int $userId): bool
    {
        return $entry->isHandlingLockActive()
            && (int) $entry->handling_by_user_id !== $userId;
    }

    protected function handlingUserLabel(PayoutEntry $entry): string
    {
        return $entry->handlingUser?->name ?: 'another user';
    }
}
