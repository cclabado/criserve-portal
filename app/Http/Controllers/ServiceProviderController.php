<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Document;
use App\Notifications\UpdatedStatementUploadedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function dashboard(Request $request)
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');

        $applications = Application::with([
                'client',
                'beneficiary.relationshipData',
                'assistanceType',
                'assistanceSubtype',
                'assistanceDetail',
                'documents',
                'socialWorker',
                'approvingOfficer',
            ])
            ->where('service_provider_id', $provider->id)
            ->whereHas('modeOfAssistance', fn ($query) => $query->where('name', 'Guarantee Letter'))
            ->whereIn('status', ['approved', 'released'])
            ->latest('updated_at')
            ->get();

        $pendingStatementCount = $applications->filter(function (Application $application) {
            return ! $application->documents->contains(fn ($document) => $document->document_type === 'Updated Statement of Account');
        })->count();

        return view('service-provider.dashboard', [
            'provider' => $provider,
            'applications' => $applications,
            'pendingStatementCount' => $pendingStatementCount,
        ]);
    }

    public function uploadUpdatedStatement(Request $request, Application $application): RedirectResponse
    {
        $provider = $request->user()->serviceProvider;

        abort_unless($provider, 403, 'No service provider account is linked to this login.');
        abort_unless((int) $application->service_provider_id === (int) $provider->id, 403);

        $request->validate([
            'statement_file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('statement_file');
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('documents', $filename, 'public');

        Document::create([
            'application_id' => $application->id,
            'document_type' => 'Updated Statement of Account',
            'file_name' => $filename,
            'file_path' => $path,
            'remarks' => 'Uploaded by service provider '.$provider->name,
        ]);

        $application->loadMissing('socialWorker', 'approvingOfficer');

        if ($application->socialWorker) {
            $application->socialWorker->notify(new UpdatedStatementUploadedNotification($application, $filename));
        }

        if ($application->approvingOfficer) {
            $application->approvingOfficer->notify(new UpdatedStatementUploadedNotification($application, $filename));
        }

        return redirect()
            ->route('service-provider.dashboard')
            ->with('success', 'Updated statement of account uploaded successfully.');
    }
}
