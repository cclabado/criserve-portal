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

        $applications = $this->providerApplicationQuery($provider->id)
            ->latest('updated_at')
            ->get();

        $totalAssigned = $applications->count();
        $pendingStatementCount = $applications->filter(function (Application $application) {
            return ! $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
        })->count();
        $submittedStatementCount = $applications->filter(function (Application $application) {
            return $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
        })->count();
        $forProcessingCount = $applications->filter(function (Application $application) {
            $hasUpdatedStatement = $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');

            return $hasUpdatedStatement && $application->gl_payment_status !== 'paid';
        })->count();
        $pendingReviewCount = $applications->where('gl_soa_status', 'pending_review')->count();
        $returnedCount = $applications->where('gl_soa_status', 'returned_for_compliance')->count();
        $processedCount = $applications->where('gl_soa_status', 'processed')->count();
        $paidCount = $applications->where('gl_payment_status', 'paid')->count();
        $releasedCount = $applications->where('status', 'released')->count();
        $totalFinalAmount = $applications->sum(fn (Application $application) => $application->effectiveDisplayedAmount());
        $completionRate = $totalAssigned > 0
            ? (int) round(($paidCount / $totalAssigned) * 100)
            : 0;

        $priorityApplications = $applications
            ->filter(fn (Application $application) => in_array($application->gl_soa_status, ['awaiting_upload', 'returned_for_compliance', 'pending_review'], true))
            ->sortBy(function (Application $application) {
                return match ($application->gl_soa_status) {
                    'returned_for_compliance' => 0,
                    'awaiting_upload' => 1,
                    'pending_review' => 2,
                    default => 3,
                };
            })
            ->values();

        $recentlyProcessed = $applications
            ->filter(fn (Application $application) => $application->gl_soa_status === 'processed')
            ->take(5)
            ->values();

        return view('service-provider.dashboard', [
            'provider' => $provider,
            'defaultBankAccount' => $provider->defaultBankAccount,
            'applications' => $applications,
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

        $applications = $this->providerApplicationQuery($provider->id)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                        });
                });
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
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $applicationCollection = method_exists($applications, 'getCollection')
            ? $applications->getCollection()
            : collect($applications);

        $uploadedCount = $applicationCollection->filter(function (Application $application) {
            return $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
        })->count();

        $pendingUploadCount = $applicationCollection->count() - $uploadedCount;

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

        $application = $this->providerApplicationQuery($provider->id)
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

        $application = $this->providerApplicationQuery($provider->id)
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

        Document::create([
            'application_id' => $application->id,
            'service_provider_bank_account_id' => $bankAccount?->id,
            'document_type' => $documentType,
            'file_name' => $storedDocument['file_name'],
            'file_path' => $storedDocument['path'],
            'storage_disk' => $storedDocument['disk'],
            'mime_type' => $storedDocument['mime_type'],
            'file_size' => $storedDocument['file_size'],
            'file_hash' => $storedDocument['file_hash'],
            'remarks' => $remarks,
            'bank_name_snapshot' => $bankAccount?->resolvedBankName(),
            'account_name_snapshot' => $bankAccount?->account_name,
            'account_number_snapshot' => $bankAccount?->account_number,
            'branch_name_snapshot' => $bankAccount?->branch_name,
        ]);

        return $storedDocument;
    }

    protected function documentTypeDocuments(Collection $documents, string $documentType): Collection
    {
        return $documents
            ->filter(fn (Document $document) => $document->document_type === $documentType)
            ->sortByDesc(fn (Document $document) => $document->created_at ?? now())
            ->values();
    }

    protected function providerApplicationQuery(int $providerId)
    {
        return Application::with([
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
            ])
            ->where('service_provider_id', $providerId)
            ->whereHas('modeOfAssistance', fn ($query) => $query->where('name', 'Guarantee Letter'))
            ->whereIn('status', ['approved', 'released']);
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
