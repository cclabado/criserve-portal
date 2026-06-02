<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Bank;
use App\Models\Document;
use App\Models\ServiceProviderBankAccount;
use App\Notifications\UpdatedStatementUploadedNotification;
use App\Services\AuditLogService;
use App\Services\DocumentSecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ServiceProviderController extends Controller
{
    public function __construct(
        protected DocumentSecurityService $documentSecurity,
        protected AuditLogService $auditLogs
    ) {
    }

    public function dashboard(Request $request)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $baseQuery = $this->providerApplicationDashboardQuery($provider->id);
        $statementExistsConstraint = fn ($query) => $query->where('document_type', 'Updated Statement of Account');

        $totalAssigned = (clone $baseQuery)->count();
        $pendingStatementCount = (clone $baseQuery)
            ->whereDoesntHave('documents', $statementExistsConstraint)
            ->count();
        $submittedStatementCount = (clone $baseQuery)
            ->whereHas('documents', $statementExistsConstraint)
            ->count();
        $forProcessingCount = (clone $baseQuery)
            ->whereHas('documents', $statementExistsConstraint)
            ->where('gl_payment_status', '!=', 'paid')
            ->count();
        $pendingReviewCount = (clone $baseQuery)->where('gl_soa_status', 'pending_review')->count();
        $returnedCount = (clone $baseQuery)->where('gl_soa_status', 'returned_for_compliance')->count();
        $processedCount = (clone $baseQuery)->where('gl_soa_status', 'processed')->count();
        $paidCount = (clone $baseQuery)->where('gl_payment_status', 'paid')->count();
        $releasedCount = (clone $baseQuery)->where('status', 'released')->count();
        $totalFinalAmount = (float) ((clone $baseQuery)->sum(DB::raw(Application::effectiveDisplayedAmountSql())));
        $completionRate = $totalAssigned > 0
            ? (int) round(($paidCount / $totalAssigned) * 100)
            : 0;

        $priorityApplications = (clone $baseQuery)
            ->whereIn('gl_soa_status', ['awaiting_upload', 'returned_for_compliance', 'pending_review'])
            ->orderByRaw("
                CASE gl_soa_status
                    WHEN 'returned_for_compliance' THEN 0
                    WHEN 'awaiting_upload' THEN 1
                    WHEN 'pending_review' THEN 2
                    ELSE 3
                END
            ")
            ->latest('updated_at')
            ->take(25)
            ->get();

        $recentlyProcessed = (clone $baseQuery)
            ->where('gl_soa_status', 'processed')
            ->latest('updated_at')
            ->take(5)
            ->get();

        return view('service-provider.dashboard', [
            'provider' => $provider,
            'defaultBankAccount' => $provider->defaultBankAccount,
            'totalAssigned' => $totalAssigned,
            'pendingStatementCount' => $pendingStatementCount,
            'submittedStatementCount' => $submittedStatementCount,
            'forProcessingCount' => $forProcessingCount,
            'pendingReviewCount' => $pendingReviewCount,
            'returnedCount' => $returnedCount,
            'processedCount' => $processedCount,
            'paidCount' => $paidCount,
            'releasedCount' => $releasedCount,
            'totalFinalAmount' => $totalFinalAmount,
            'completionRate' => $completionRate,
            'priorityApplications' => $priorityApplications,
            'recentlyProcessed' => $recentlyProcessed,
        ]);
    }

    public function letters(Request $request)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', 'all'),
        ];

        $statementExistsConstraint = fn ($query) => $query->where('document_type', 'Updated Statement of Account');

        $filteredQuery = $this->providerApplicationListQuery($provider->id)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $this->applyPersonNameSearch($query, $filters['search']);
            })
            ->when($filters['status'] !== 'all', function ($query) use ($filters) {
                if ($filters['status'] === 'uploaded') {
                    $query->whereHas('documents', fn ($documentQuery) => $documentQuery->where('document_type', 'Updated Statement of Account'));

                    return;
                }

                if ($filters['status'] === 'pending_upload') {
                    $query->whereDoesntHave('documents', fn ($documentQuery) => $documentQuery->where('document_type', 'Updated Statement of Account'));

                    return;
                }

                $query->where('gl_soa_status', $filters['status']);
            });

        $applications = (clone $filteredQuery)
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $uploadedCount = (clone $filteredQuery)
            ->whereHas('documents', $statementExistsConstraint)
            ->count();
        $pendingUploadCount = (clone $filteredQuery)
            ->whereDoesntHave('documents', $statementExistsConstraint)
            ->count();

        return view('service-provider.letters', [
            'provider' => $provider,
            'applications' => $applications,
            'filters' => $filters,
            'uploadedCount' => $uploadedCount,
            'pendingUploadCount' => $pendingUploadCount,
        ]);
    }

    public function show(Request $request, Application $application)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $application = $this->providerApplicationDetailQuery($provider->id)
            ->whereKey($application->id)
            ->firstOrFail();

        return view('service-provider/show', [
            'provider' => $provider,
            'application' => $application,
            'bankAccounts' => $provider->bankAccounts()->where('is_active', true)->get(),
            'defaultBankAccount' => $provider->defaultBankAccount,
            'statementDocuments' => $this->documentTypeDocuments($application->documents, 'Updated Statement of Account'),
            'supportingDocuments' => $this->documentTypeDocuments($application->documents, 'Other Supporting Document'),
        ]);
    }

    public function bankAccounts(Request $request)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        return view('service-provider.bank-accounts', [
            'provider' => $provider->load(['bankAccounts', 'defaultBankAccount']),
            'bankOptions' => Bank::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeBankAccount(Request $request): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $validated = $request->validate([
            'bank_id' => ['required', 'exists:banks,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $bank = Bank::query()->findOrFail((int) $validated['bank_id']);

        DB::transaction(function () use ($provider, $validated, $bank) {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || ! $provider->bankAccounts()->where('is_active', true)->exists();

            if ($makeDefault) {
                $provider->bankAccounts()->update(['is_default' => false]);
            }

            $provider->bankAccounts()->create([
                'bank_id' => $bank->id,
                'bank_name' => $bank->name,
                'account_name' => trim($validated['account_name']),
                'account_number' => trim($validated['account_number']),
                'branch_name' => filled($validated['branch_name'] ?? null) ? trim((string) $validated['branch_name']) : null,
                'is_default' => $makeDefault,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('service-provider.bank-accounts')
            ->with('success', 'Bank account added successfully.');
    }

    public function setDefaultBankAccount(Request $request, ServiceProviderBankAccount $bankAccount): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');
        abort_unless((int) $bankAccount->service_provider_id === (int) $provider->id, 403);

        DB::transaction(function () use ($provider, $bankAccount) {
            $provider->bankAccounts()->update(['is_default' => false]);

            $bankAccount->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('service-provider.bank-accounts')
            ->with('success', 'Default bank account updated successfully.');
    }

    public function guaranteeLetter(Request $request, Application $application)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $application = $this->providerApplicationDetailQuery($provider->id)
            ->whereKey($application->id)
            ->firstOrFail();

        return view('social-worker.guarantee-letter', compact('application'));
    }

    public function uploadUpdatedStatement(Request $request, Application $application): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');
        abort_unless((int) $application->service_provider_id === (int) $provider->id, 403);

        $selectedBankAccount = $this->resolveProviderBankAccount(
            $provider,
            $request->input('service_provider_bank_account_id')
        );

        $validatedAmount = $request->validate([
            'gl_actual_utilized_amount' => ['required', 'numeric', 'min:0'],
        ]);

        if (! $selectedBankAccount) {
            return back()
                ->withErrors(['service_provider_bank_account_id' => 'Select a bank account for the statement of account.'])
                ->withInput();
        }

        $storedDocument = $this->storeProviderDocument(
            $request,
            $application,
            $provider->name,
            'statement_file',
            'Updated Statement of Account',
            $selectedBankAccount
        );

        $application->update([
            'gl_actual_utilized_amount' => round((float) $validatedAmount['gl_actual_utilized_amount'], 2),
            'gl_payment_status' => 'for_processing',
            'gl_soa_status' => 'pending_review',
            'gl_soa_review_notes' => null,
            'gl_soa_reviewed_by' => null,
            'gl_soa_reviewed_at' => null,
        ]);

        $application->loadMissing('socialWorker', 'approvingOfficer');

        if ($application->socialWorker) {
            $application->socialWorker->notify(new UpdatedStatementUploadedNotification($application, $storedDocument['file_name']));
        }

        if ($application->approvingOfficer) {
            $application->approvingOfficer->notify(new UpdatedStatementUploadedNotification($application, $storedDocument['file_name']));
        }

        $this->auditLogs->log($request, 'document.upload.updated_statement', $application, [
            'document_type' => 'Updated Statement of Account',
            'file_name' => $storedDocument['file_name'],
        ]);

        return redirect()
            ->to(url()->previous() ?: route('service-provider.dashboard'))
            ->with('success', 'Updated statement of account uploaded successfully.');
    }

    public function uploadSupportingDocument(Request $request, Application $application): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');
        abort_unless((int) $application->service_provider_id === (int) $provider->id, 403);

        $storedDocument = $this->storeProviderDocument(
            $request,
            $application,
            $provider->name,
            'supporting_document_file',
            'Other Supporting Document'
        );

        $this->auditLogs->log($request, 'document.upload.supporting_document', $application, [
            'document_type' => 'Other Supporting Document',
            'file_name' => $storedDocument['file_name'],
        ]);

        return redirect()
            ->to(url()->previous() ?: route('service-provider.dashboard'))
            ->with('success', 'Supporting document uploaded successfully.');
    }

    public function submitAttachments(Request $request, Application $application): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');
        abort_unless((int) $application->service_provider_id === (int) $provider->id, 403);

        $request->validate([
            'statement_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'supporting_document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'attachment_remarks' => ['nullable', 'string', 'max:1000'],
            'service_provider_bank_account_id' => ['nullable', 'integer'],
            'gl_actual_utilized_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $remarks = trim((string) $request->input('attachment_remarks', ''));

        if (! $request->hasFile('statement_file') && ! $request->hasFile('supporting_document_file')) {
            return back()
                ->withErrors(['attachments' => 'Attach at least one file before submitting.'])
                ->withInput();
        }

        $uploadedNames = [];

        if ($request->hasFile('statement_file')) {
            if (! filled($request->input('gl_actual_utilized_amount'))) {
                return back()
                    ->withErrors(['gl_actual_utilized_amount' => 'Enter the actual utilized amount for the statement of account.'])
                    ->withInput();
            }

            $selectedBankAccount = $this->resolveProviderBankAccount(
                $provider,
                $request->input('service_provider_bank_account_id')
            );

            if (! $selectedBankAccount) {
                return back()
                    ->withErrors(['service_provider_bank_account_id' => 'Select a bank account for the statement of account.'])
                    ->withInput();
            }

            $storedStatement = $this->storeProviderUploadedFile(
                $request->file('statement_file'),
                $application,
                $provider->name,
                'Updated Statement of Account',
                $remarks,
                $selectedBankAccount
            );

            $uploadedNames[] = $storedStatement['file_name'];

            $application->update([
                'gl_actual_utilized_amount' => round((float) $request->input('gl_actual_utilized_amount'), 2),
                'gl_payment_status' => 'for_processing',
                'gl_soa_status' => 'pending_review',
                'gl_soa_review_notes' => null,
                'gl_soa_reviewed_by' => null,
                'gl_soa_reviewed_at' => null,
            ]);

            $application->loadMissing('socialWorker', 'approvingOfficer');

            if ($application->socialWorker) {
                $application->socialWorker->notify(new UpdatedStatementUploadedNotification($application, $storedStatement['file_name']));
            }

            if ($application->approvingOfficer) {
                $application->approvingOfficer->notify(new UpdatedStatementUploadedNotification($application, $storedStatement['file_name']));
            }

            $this->auditLogs->log($request, 'document.upload.updated_statement', $application, [
                'document_type' => 'Updated Statement of Account',
                'file_name' => $storedStatement['file_name'],
            ]);
        }

        if ($request->hasFile('supporting_document_file')) {
            $storedSupporting = $this->storeProviderUploadedFile(
                $request->file('supporting_document_file'),
                $application,
                $provider->name,
                'Other Supporting Document',
                $remarks
            );

            $uploadedNames[] = $storedSupporting['file_name'];

            $this->auditLogs->log($request, 'document.upload.supporting_document', $application, [
                'document_type' => 'Other Supporting Document',
                'file_name' => $storedSupporting['file_name'],
            ]);
        }

        return redirect()
            ->to(url()->previous() ?: route('service-provider.dashboard'))
            ->with('success', 'Attachments submitted successfully: '.implode(', ', $uploadedNames));
    }

    protected function storeProviderDocument(
        Request $request,
        Application $application,
        string $providerName,
        string $fieldName,
        string $documentType,
        ?ServiceProviderBankAccount $bankAccount = null
    ): array {
        $request->validate([
            $fieldName => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $request->file($fieldName);
        
        return $this->storeProviderUploadedFile($file, $application, $providerName, $documentType, '', $bankAccount);
    }

    protected function storeProviderUploadedFile(
        UploadedFile $file,
        Application $application,
        string $providerName,
        string $documentType,
        string $extraRemarks = '',
        ?ServiceProviderBankAccount $bankAccount = null
    ): array {
        $storedDocument = $this->documentSecurity->secureStore($file);

        $bankAccountSummary = $bankAccount?->displayLabel();
        $remarks = trim('Uploaded by service provider '.$providerName
            .($bankAccountSummary ? ' | Bank: '.$bankAccountSummary : '')
            .($extraRemarks !== '' ? ' | Remarks: '.$extraRemarks : ''));

        $document = Document::create([
            'application_id' => $application->id,
            'service_provider_bank_account_id' => $bankAccount?->id,
            'document_type' => $documentType,
            'file_name' => $storedDocument['file_name'],
            'file_path' => $storedDocument['path'],
            'storage_disk' => $storedDocument['disk'],
            'mime_type' => $storedDocument['mime_type'],
            'file_size' => $storedDocument['file_size'],
            'file_hash' => $storedDocument['file_hash'],
            'scan_status' => $storedDocument['scan_status'] ?? null,
            'scan_message' => $storedDocument['scan_message'] ?? null,
            'scan_requested_at' => $storedDocument['scan_requested_at'] ?? null,
            'scanned_at' => $storedDocument['scanned_at'] ?? null,
            'remarks' => $remarks,
            'bank_name_snapshot' => $bankAccount?->resolvedBankName(),
            'account_name_snapshot' => $bankAccount?->account_name,
            'account_number_snapshot' => $bankAccount?->account_number,
            'branch_name_snapshot' => $bankAccount?->branch_name,
        ]);

        $this->documentSecurity->queueStoredDocumentScan($document);

        return $storedDocument;
    }

    protected function documentTypeDocuments(Collection $documents, string $documentType): Collection
    {
        return $documents
            ->filter(fn (Document $document) => $document->document_type === $documentType)
            ->sortByDesc(fn (Document $document) => $document->created_at ?? now())
            ->values();
    }

    protected function providerApplicationBaseQuery(int $providerId)
    {
        return Application::query()
            ->where('service_provider_id', $providerId)
            ->whereHas('modeOfAssistance', fn ($query) => $query->where('name', 'Guarantee Letter'))
            ->whereIn('status', ['approved', 'released']);
    }

    protected function providerApplicationDashboardQuery(int $providerId)
    {
        return $this->providerApplicationBaseQuery($providerId)
            ->with([
                'client:id,first_name,last_name',
                'assistanceSubtype:id,name',
                'assistanceDetail:id,name',
            ])
            ->withExists([
                'documents as has_updated_statement' => fn ($query) => $query->where('document_type', 'Updated Statement of Account'),
            ]);
    }

    protected function providerApplicationListQuery(int $providerId)
    {
        return $this->providerApplicationBaseQuery($providerId)
            ->with([
                'client:id,first_name,last_name,birthdate',
                'beneficiary:id,application_id,first_name,middle_name,last_name,extension_name,relationship_id',
                'beneficiary.relationshipData:id,name',
                'assistanceSubtype:id,name',
                'assistanceDetail:id,name',
            ])
            ->withExists([
                'documents as has_updated_statement' => fn ($query) => $query->where('document_type', 'Updated Statement of Account'),
            ]);
    }

    protected function providerApplicationDetailQuery(int $providerId)
    {
        return $this->providerApplicationBaseQuery($providerId)
            ->with([
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'modeOfAssistance',
                'serviceProvider',
                'documents.bankAccount',
                'socialWorker',
                'approvingOfficer',
                'assistanceRecommendations.assistanceType',
                'assistanceRecommendations.assistanceSubtype',
                'assistanceRecommendations.assistanceDetail',
                'assistanceRecommendations.modeOfAssistance',
                'assistanceRecommendations.referralInstitution',
            ]);
    }

    protected function applyPersonNameSearch($query, string $search): void
    {
        $term = trim($search);

        if ($term === '') {
            return;
        }

        $tokens = collect(preg_split('/\s+/', $term) ?: [])
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->values();

        $query->where(function ($inner) use ($term, $tokens) {
            $inner->where('reference_no', 'like', "%{$term}%")
                ->orWhereHas('client', function ($clientQuery) use ($term, $tokens) {
                    $clientQuery->where(function ($nameQuery) use ($term, $tokens) {
                        $nameQuery->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");

                        if ($tokens->count() >= 2) {
                            foreach ($tokens as $token) {
                                $nameQuery->where(function ($tokenQuery) use ($token) {
                                    $tokenQuery->where('first_name', 'like', "%{$token}%")
                                        ->orWhere('last_name', 'like', "%{$token}%");
                                });
                            }
                        }
                    });
                });
        });
    }

    protected function resolveProviderBankAccount($provider, ?string $selectedId): ?ServiceProviderBankAccount
    {
        if (filled($selectedId)) {
            return $provider->bankAccounts()
                ->where('is_active', true)
                ->whereKey((int) $selectedId)
                ->first();
        }

        return $provider->defaultBankAccount;
    }
}
