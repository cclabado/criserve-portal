<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlPaymentProcessorController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function dashboard(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'payment_status' => (string) $request->input('payment_status', 'all'),
            'soa_status' => (string) $request->input('soa_status', 'all'),
        ];

        $applications = $this->baseQuery()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        })
                        ->orWhereHas('serviceProvider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['payment_status'] !== 'all', fn ($query) => $query->where('gl_payment_status', $filters['payment_status']))
            ->when($filters['soa_status'] !== 'all', fn ($query) => $query->where('gl_soa_status', $filters['soa_status']))
            ->latest('updated_at')
            ->get();

        $statsQuery = $this->baseQuery();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'awaiting_upload' => (clone $statsQuery)->where('gl_soa_status', 'awaiting_upload')->count(),
            'pending_review' => (clone $statsQuery)->where('gl_soa_status', 'pending_review')->count(),
            'returned' => (clone $statsQuery)->where('gl_soa_status', 'returned_for_compliance')->count(),
            'processed' => (clone $statsQuery)->where('gl_soa_status', 'processed')->count(),
            'paid' => (clone $statsQuery)->where('gl_payment_status', 'paid')->count(),
        ];

        return view('gl-payment-processor/dashboard', [
            'applications' => $applications,
            'filters' => $filters,
            'stats' => $stats,
            'paymentStatusOptions' => ['unpaid', 'paid'],
            'soaStatusOptions' => ['awaiting_upload', 'pending_review', 'returned_for_compliance', 'processed'],
        ]);
    }

    public function show(Application $application): View
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return view('gl-payment-processor/show', [
            'application' => $application,
        ]);
    }

    public function guaranteeLetter(Application $application): View
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return view('social-worker.guarantee-letter', compact('application'));
    }

    public function updateSoaReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'gl_soa_status' => ['required', 'in:pending_review,returned_for_compliance,processed'],
            'gl_soa_review_notes' => ['nullable', 'string'],
        ]);

        $latestStatement = $this->latestUpdatedStatement($application);

        if (! $latestStatement) {
            return back()->withErrors([
                'gl_soa_status' => 'A service provider must upload an updated statement of account before it can be reviewed.',
            ]);
        }

        if ($validated['gl_soa_status'] === 'returned_for_compliance' && blank($validated['gl_soa_review_notes'] ?? null)) {
            return back()->withErrors([
                'gl_soa_review_notes' => 'Review notes are required when returning the updated statement for compliance.',
            ]);
        }

        if ($validated['gl_soa_status'] === 'processed' && blank($application->gl_payment_status)) {
            $application->gl_payment_status = 'unpaid';
        }

        $application->update([
            'gl_soa_status' => $validated['gl_soa_status'],
            'gl_soa_review_notes' => filled($validated['gl_soa_review_notes'] ?? null) ? trim((string) $validated['gl_soa_review_notes']) : null,
            'gl_soa_reviewed_by' => $request->user()->id,
            'gl_soa_reviewed_at' => now(),
        ]);

        $this->auditLogs->log($request, 'gl_soa.reviewed', $application, [
            'soa_status' => $validated['gl_soa_status'],
            'review_notes' => $validated['gl_soa_review_notes'] ?? null,
            'statement_document_id' => $latestStatement->id,
        ]);

        return redirect()
            ->route('gl-payment-processor.show', $application->id)
            ->with('success', 'Updated SOA review status saved successfully.');
    }

    public function updatePaymentStatus(Request $request, Application $application): RedirectResponse
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'gl_payment_status' => ['required', 'in:unpaid,paid'],
        ]);

        $application->update([
            'gl_payment_status' => $validated['gl_payment_status'],
        ]);

        $this->auditLogs->log($request, 'gl_payment.status_updated', $application, [
            'payment_status' => $validated['gl_payment_status'],
        ]);

        return redirect()
            ->route('gl-payment-processor.show', $application->id)
            ->with('success', 'Guarantee letter payment status updated successfully.');
    }

    protected function baseQuery()
    {
        return Application::with([
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'modeOfAssistance',
                'serviceProvider',
                'documents',
                'socialWorker',
                'approvingOfficer',
                'assistanceRecommendations.assistanceType',
                'assistanceRecommendations.assistanceSubtype',
                'assistanceRecommendations.assistanceDetail',
                'assistanceRecommendations.modeOfAssistance',
                'assistanceRecommendations.referralInstitution',
                'glSoaReviewer',
            ])
            ->whereHas('modeOfAssistance', fn ($query) => $query->where('name', 'Guarantee Letter'))
            ->whereIn('status', ['approved', 'released']);
    }

    protected function latestUpdatedStatement(Application $application)
    {
        return $application->documents
            ->where('document_type', 'Updated Statement of Account')
            ->sortByDesc('created_at')
            ->first();
    }
}
