<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($document->file_path), 404);

        $mimeType = $disk->mimeType($document->file_path) ?: 'application/octet-stream';
        $isInlinePreview = str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf';

        return view('documents.show', [
            'document' => $document->loadMissing('application.client', 'application.assistanceType'),
            'mimeType' => $mimeType,
            'isInlinePreview' => $isInlinePreview,
        ]);
    }

    public function stream(Request $request, Document $document): StreamedResponse
    {
        $this->authorizeDocument($request, $document);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, $document->file_name, [
            'Content-Disposition' => 'inline; filename="'.$document->file_name.'"',
        ]);
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorizeDocument($request, $document);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download($document->file_path, $document->file_name);
    }

    protected function authorizeDocument(Request $request, Document $document): void
    {
        $user = $request->user();
        $document->loadMissing('application');

        abort_unless($user && $document->application, 403);

        if (in_array($user->role, ['admin', 'social_worker', 'approving_officer'], true)) {
            return;
        }

        if ($user->role === 'service_provider') {
            abort_unless(
                $user->serviceProvider && (int) $document->application->service_provider_id === (int) $user->serviceProvider->id,
                403
            );

            return;
        }

        abort_unless(
            $user->role === 'client' && (int) $document->application->user_id === (int) $user->id,
            403
        );
    }
}
