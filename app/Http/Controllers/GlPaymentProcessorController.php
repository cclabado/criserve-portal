<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\Application;
use App\Models\Document;
use App\Models\FinanceFundSource;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlPaymentProcessorController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function dashboard(Request $request): View
    {
        $baseQuery = $this->baseQuery();
        $statementExistsConstraint = fn ($query) => $query->where('document_type', 'Updated Statement of Account');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'awaiting_soa' => (clone $baseQuery)
                ->whereDoesntHave('documents', $statementExistsConstraint)
                ->count(),
            'for_processing' => (clone $baseQuery)
                ->whereHas('documents', $statementExistsConstraint)
                ->where('gl_payment_status', '!=', 'paid')
                ->count(),
            'paid' => (clone $baseQuery)
                ->where('gl_payment_status', 'paid')
                ->count(),
        ];

        $returnedCases = (clone $baseQuery)
            ->where('gl_soa_status', 'returned_for_compliance')
            ->latest('updated_at')
            ->take(5)
            ->get();

        $recentSubmissions = (clone $baseQuery)
            ->whereHas('documents', $statementExistsConstraint)
            ->latest('gl_soa_reviewed_at')
            ->latest('updated_at')
            ->take(5)
            ->get();

        $providerLoad = (clone $baseQuery)
            ->leftJoin('service_providers', 'service_providers.id', '=', 'applications.service_provider_id')
            ->selectRaw("COALESCE(service_providers.name, 'Unassigned Provider') as provider")
            ->selectRaw('COUNT(applications.id) as total')
            ->selectRaw("SUM(CASE WHEN NOT EXISTS (
                SELECT 1
                FROM documents
                WHERE documents.application_id = applications.id
                    AND documents.document_type = 'Updated Statement of Account'
            ) THEN 1 ELSE 0 END) as awaiting_soa")
            ->groupBy('service_providers.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

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
            'scope' => (string) $request->input('scope', 'active'),
        ];

        $applications = $this->filteredQueueQuery($filters)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('gl-payment-processor/queue', [
            'applications' => $applications,
            'filters' => $filters,
            'paymentStatusOptions' => ['awaiting_soa', 'for_processing', 'paid'],
        ]);
    }

    public function show(Application $application): View
    {
        $application = $this->baseQuery(true)
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

    public function ors(Application $application): View
    {
        $application = $this->baseQuery(true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlOrsView($application);
    }

    public function dv(Application $application): View
    {
        $application = $this->baseQuery(true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlDvView($application);
    }

    public function lddapAda(Application $application): View
    {
        $application = $this->baseQuery(true)
            ->whereKey($application->id)
            ->firstOrFail();

        return $this->renderGlLddapAdaView($application);
    }

    public function guaranteeLetter(Application $application): View
    {
        $application = $this->baseQuery(true)
            ->whereKey($application->id)
            ->firstOrFail();

        return view('social-worker.guarantee-letter', compact('application'));
    }

    public function updateSoaReview(Request $request, Application $application): RedirectResponse
    {
        $application = $this->baseQuery(true)
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
            'gl_payment_status' => 'for_compliance_service_provider',
            'gl_soa_status' => 'returned_for_compliance',
            'gl_soa_review_notes' => trim((string) $validated['gl_soa_review_notes']),
            'gl_soa_reviewed_by' => $request->user()->id,
            'gl_soa_reviewed_at' => now(),
        ]);

        $this->auditLogs->log($request, 'gl_soa.reviewed', $application, [
            'payment_status' => 'for_compliance_service_provider',
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
            'gl_fund_cluster' => ['required', 'string', 'max:255'],
            'gl_responsibility_center' => ['required', 'string', 'max:255'],
            'gl_mfo_pap' => ['required', 'string', 'max:30'],
            'gl_mode_of_payment' => ['required', 'string', 'max:50'],
            'gl_payee_tin' => ['nullable', 'string', 'max:255'],
            'gl_budget_remarks' => ['nullable', 'string', 'max:1500'],
        ]);

        $latestStatement = $this->latestUpdatedStatement($application);

        if (! $latestStatement) {
            return back()->withErrors([
                'gl_finance_fund_source' => 'A service provider must upload an updated statement of account before the case can be submitted for approving officer review.',
            ]);
        }

        $today = now()->toDateString();
        $orsNumber = $application->gl_ors_number ?: $this->generateOrsNumber($application);
        $dvNumber = $application->gl_dv_number ?: $this->generateDvNumber($application);

        $application->update([
            'gl_soa_status' => 'processed',
            'gl_soa_review_notes' => null,
            'gl_soa_reviewed_by' => $request->user()->id,
            'gl_soa_reviewed_at' => now(),
            'gl_finance_fund_source' => trim((string) $validated['gl_finance_fund_source']),
            'gl_fund_cluster' => trim((string) $validated['gl_fund_cluster']),
            'gl_responsibility_center' => trim((string) $validated['gl_responsibility_center']),
            'gl_mfo_pap' => trim((string) $validated['gl_mfo_pap']),
            'gl_mode_of_payment' => trim((string) $validated['gl_mode_of_payment']),
            'gl_payee_tin' => filled($validated['gl_payee_tin'] ?? null) ? trim((string) $validated['gl_payee_tin']) : null,
            'gl_ors_number' => $orsNumber,
            'gl_ors_date' => $application->gl_ors_date ?: $today,
            'gl_dv_number' => $dvNumber,
            'gl_dv_date' => $application->gl_dv_date ?: $today,
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
            'fund_cluster' => $validated['gl_fund_cluster'],
            'responsibility_center' => $validated['gl_responsibility_center'],
            'mfo_pap' => $validated['gl_mfo_pap'],
            'mode_of_payment' => $validated['gl_mode_of_payment'],
            'payee_tin' => $validated['gl_payee_tin'] ?? null,
            'ors_number' => $orsNumber,
            'dv_number' => $dvNumber,
            'budget_remarks' => $validated['gl_budget_remarks'] ?? null,
            'statement_document_id' => $latestStatement->id,
        ]);

        return redirect()
            ->route('gl-payment-processor.show', $application->id)
            ->with('success', 'Case submitted to the approving officer successfully.');
    }

    protected function baseQuery(bool $withDocuments = false)
    {
        $relations = [
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'modeOfAssistance',
                'serviceProvider',
                'socialWorker',
                'approvingOfficer.position',
                'assistanceRecommendations.assistanceType',
                'assistanceRecommendations.assistanceSubtype',
                'assistanceRecommendations.assistanceDetail',
                'assistanceRecommendations.modeOfAssistance',
                'assistanceRecommendations.referralInstitution',
                'glSoaReviewer',
                'glBudgetReviewer',
                'glBudgetApprover.position',
                'glAccountingApprover.position',
            ];

        if ($withDocuments) {
            $relations[] = 'documents';
        }

        return Application::with($relations)
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
            })
            ->when(($filters['scope'] ?? 'active') === 'finished', function ($query) {
                $query->where(function ($handledQuery) {
                    $handledQuery->where('gl_soa_reviewed_by', auth()->id())
                        ->orWhere('gl_budget_reviewed_by', auth()->id());
                })->where('gl_payment_status', '!=', 'for_compliance_gl_processor');
            })
            ->when(($filters['scope'] ?? 'active') !== 'finished', function ($query) {
                $query->where(function ($scopeQuery) {
                    $scopeQuery->where('gl_payment_status', 'for_compliance_gl_processor')
                        ->orWhere(function ($notHandledQuery) {
                            $notHandledQuery
                                ->where(function ($soaQuery) {
                                    $soaQuery->whereNull('gl_soa_reviewed_by')
                                        ->orWhere('gl_soa_reviewed_by', '!=', auth()->id());
                                })
                                ->where(function ($budgetQuery) {
                                    $budgetQuery->whereNull('gl_budget_reviewed_by')
                                        ->orWhere('gl_budget_reviewed_by', '!=', auth()->id());
                                });
                        });
                });
            });
    }

    protected function latestUpdatedStatement(Application $application)
    {
        return $application->documents
            ->where('document_type', 'Updated Statement of Account')
            ->sortByDesc(fn (Document $document) => $document->created_at?->getTimestamp() ?? 0)
            ->first();
    }

    protected function documentTypeDocuments(Application $application, string $documentType)
    {
        return $application->documents
            ->where('document_type', $documentType)
            ->sortByDesc('created_at')
            ->values();
    }

    protected function generateOrsNumber(Application $application): string
    {
        $stamp = Carbon::now()->format('Y-m');

        return sprintf('02-01101101-%s-%05d', $stamp, (int) $application->id);
    }

    protected function generateDvNumber(Application $application): string
    {
        $stamp = Carbon::now()->format('Y-m');

        return sprintf('DV-%s-%05d', $stamp, (int) $application->id);
    }
}
