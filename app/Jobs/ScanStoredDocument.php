<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentSecurityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScanStoredDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $documentId
    ) {
        $this->onQueue((string) config('security.uploads.scan_queue', 'documents'));
    }

    public function handle(DocumentSecurityService $documentSecurity): void
    {
        $document = Document::query()->find($this->documentId);

        if (! $document) {
            return;
        }

        $documentSecurity->scanStoredDocument($document);
    }
}
