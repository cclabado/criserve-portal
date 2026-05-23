<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\FinanceFundSource;
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
        $applications = $this->baseQuery()
            ->latest('updated_at')
            ->get();

        $stats = [
            'total' => $applications->count(),
            'awaiting_soa' => $applications->filter(fn (Application $application) => ! $this->latestUpdatedStatement($application))->count(),
            'for_processing' => $applications->filter(function (Application $application) {
                return $this->latestUpdatedStatement($application) && $application->gl_payment_status !== 'paid';
            })->count(),
            'paid' => $applications->where('gl_payment_status', 'paid')->count(),
        ];

        $returnedCases = $applications
            ->where('gl_soa_status', 'returned_for_compliance')
            ->sortByDesc('updated_at')
            ->take(5)
            ->values();

        $recentSubmissions = $applications
            ->filter(fn (Application $application) => (bool) $this->latestUpdatedStatement($application))
            ->sortByDesc(function (Application $application) {
                return optional($this->latestUpdatedStatement($application))->created_at;
            })
            ->take(5)
            ->values();

        $providerLoad = $applications
            ->groupBy(fn (Application $application) => $application->serviceProvider?->name ?? 'Unassigned Provider')
            ->map(function ($group, $providerName) {
                $total = $group->count();
                $awaitingSoa = $group->filter(fn (Application $application) => ! $this->latestUpdatedStatement($application))->count();

                return [
                    'provider' => $providerName,
                    'total' => $total,
                    'awaiting_soa' => $awaitingSoa,
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        return view('gl-payment-processor/dashboard', [
            'stats' => $stats,
            'returnedCases' => $returnedCases,
            'recentSubmissions' => $recentSubmissions,
            'providerLoad' => $providerLoad,
        ]);
    }

    public function queue(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'payment_status' => (string) $request->input('payment_status', 'all'),
        ];

        $applications = $this->filteredQueueQuery($filters)
            ->latest('updated_at')
            ->get();

        return view('gl-payment-processor/queue', [
            'applications' => $applications,
            'filters' => $filters,
            'paymentStatusOptions' => ['awaiting_soa', 'for_processing', 'paid'],
        ]);
    }

    public function show(Application $application): View
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        return view('gl-payment-processor/show', [
            'application' => $application,
            'statementDocuments' => $this->documentTypeDocuments($application, 'Updated Statement of Account'),
            'supportingDocuments' => $this->documentTypeDocuments($application, 'Other Supporting Document'),
            'financeFundSources' => FinanceFundSource::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
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
            'gl_soa_review_notes' => ['required', 'string'],
        ]);

        $latestStatement = $this->latestUpdatedStatement($application);

        if (! $latestStatement) {
            return back()->withErrors([
                'gl_soa_status' => 'A service provider must upload an updated statement of account before it can be reviewed.',
            ]);
        }

        $application->update([
            'gl_soa_status' => 'returned_for_compliance',
            'gl_soa_review_notes' => trim((string) $validated['gl_soa_review_notes']),
            'gl_soa_reviewed_by' => $request->user()->id,
            'gl_soa_reviewed_at' => now(),
        ]);

        $this->auditLogs->log($request, 'gl_soa.reviewed', $application, [
            'soa_status' => 'returned_for_compliance',
            'review_notes' => $validated['gl_soa_review_notes'],
            'statement_document_id' => $latestStatement->id,
        ]);

        return redirect()
            ->route('gl-payment-processor.show', $application->id)
            ->with('success', 'Case returned to the service provider for compliance.');
    }

    public function submitBudgetProcessing(Request $request, Application $application): RedirectResponse
    {
        $application = $this->baseQuery()
            ->whereKey($application->id)
            ->firstOrFail();

        $validated = $request->validate([
            'gl_finance_fund_source' => ['required', 'string', 'max:255'],
            'gl_budget_remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        $latestStatement = $this->latestUpdatedStatement($application);

        if (! $latestStatement) {
            return back()->withErrors([
                'gl_finance_fund_source' => 'A service provider must upload an updated statement of account before the case can be submitted for approving officer review.',
            ]);
        }

        $application->update([
            'gl_soa_status' => 'processed',
            'gl_soa_review_notes' => null,
            'gl_soa_reviewed_by' => $request->user()->id,
            'gl_soa_reviewed_at' => now(),
            'gl_finance_fund_source' => trim((string) $validated['gl_finance_fund_source']),
            'gl_budget_remarks' => filled($validated['gl_budget_remarks'] ?? null) ? trim((string) $validated['gl_budget_remarks']) : null,
            'gl_budget_reviewed_by' => $request->user()->id,
            'gl_budget_reviewed_at' => now(),
            'gl_program_approval_status' => 'pending_approval',
            'gl_program_approval_remarks' => null,
            'gl_program_approved_by' => null,
            'gl_program_approved_at' => null,
            'gl_payment_status' => 'for_processing_program_approval',
        ]);

        $this->auditLogs->log($request, 'gl_payment.submitted_for_budget_processing', $application, [
            'payment_status' => 'for_processing_program_approval',
            'fund_source' => $validated['gl_finance_fund_source'],
            'budget_remarks' => $validated['gl_budget_remarks'] ?? null,
            'statement_document_id' => $latestStatement->id,
        ]);

        return redirect()
            ->route('gl-payment-processor.show', $application->id)
            ->with('success', 'Case submitted to the approving officer successfully.');
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
                'glBudgetReviewer',
            ])
            ->whereHas('modeOfAssistance', fn ($query) => $query->where('name', 'Guarantee Letter'))
            ->whereIn('status', ['approved', 'released']);
    }

    protected function filteredQueueQuery(array $filters)
    {
        return $this->baseQuery()
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
            ->when($filters['payment_status'] !== 'all', function ($query) use ($filters) {
                if ($filters['payment_status'] === 'awaiting_soa') {
                    $query->whereDoesntHave('documents', fn ($documentQuery) => $documentQuery->where('document_type', 'Updated Statement of Account'));

                    return;
                }

                if ($filters['payment_status'] === 'for_processing') {
                    $query->whereHas('documents', fn ($documentQuery) => $documentQuery->where('document_type', 'Updated Statement of Account'))
                        ->where('gl_payment_status', '!=', 'paid');

                    return;
                }

                $query->where('gl_payment_status', $filters['payment_status']);
            });
    }

    protected function latestUpdatedStatement(Application $application)
    {
        return $application->documents
            ->where('document_type', 'Updated Statement of Account')
            ->sortByDesc('created_at')
            ->first();
    }

    protected function documentTypeDocuments(Application $application, string $documentType)
    {
        return $application->documents
            ->where('document_type', $documentType)
            ->sortByDesc('created_at')
            ->values();
    }
}
