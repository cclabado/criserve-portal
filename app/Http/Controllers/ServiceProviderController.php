<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Document;
use App\Notifications\UpdatedStatementUploadedNotification;
use App\Services\AuditLogService;
use App\Services\DocumentSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $pendingStatementCount = $applications->filter(function (Application $application) {
            return ! $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
        })->count();

        $pendingReviewCount = $applications->where('gl_soa_status', 'pending_review')->count();
        $returnedCount = $applications->where('gl_soa_status', 'returned_for_compliance')->count();
        $processedCount = $applications->where('gl_soa_status', 'processed')->count();
        $paidCount = $applications->where('gl_payment_status', 'paid')->count();
        $releasedCount = $applications->where('status', 'released')->count();
        $totalFinalAmount = $applications->sum(fn (Application $application) => (float) ($application->final_amount ?? $application->recommended_amount ?? 0));

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
            'applications' => $applications,
            'pendingStatementCount' => $pendingStatementCount,
            'pendingReviewCount' => $pendingReviewCount,
            'returnedCount' => $returnedCount,
            'processedCount' => $processedCount,
            'paidCount' => $paidCount,
            'releasedCount' => $releasedCount,
            'totalFinalAmount' => $totalFinalAmount,
            'priorityApplications' => $priorityApplications,
            'recentlyProcessed' => $recentlyProcessed,
        ]);
    }

    public function letters(Request $request)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $applications = $this->providerApplicationQuery($provider->id)
            ->latest('updated_at')
            ->get();

        return view('service-provider.letters', [
            'provider' => $provider,
            'applications' => $applications,
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
        ]);
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

        $request->validate([
            'statement_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $request->file('statement_file');
        $storedDocument = $this->documentSecurity->secureStore($file);

        Document::create([
            'application_id' => $application->id,
            'document_type' => 'Updated Statement of Account',
            'file_name' => $storedDocument['file_name'],
            'file_path' => $storedDocument['path'],
            'storage_disk' => $storedDocument['disk'],
            'mime_type' => $storedDocument['mime_type'],
            'file_size' => $storedDocument['file_size'],
            'file_hash' => $storedDocument['file_hash'],
            'remarks' => 'Uploaded by service provider '.$provider->name,
        ]);

        $application->update([
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
                'documents',
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
}
