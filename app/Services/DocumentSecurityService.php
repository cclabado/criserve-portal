<?php

namespace App\Services;

use App\Jobs\ScanStoredDocument;
use App\Models\Document;
use App\Support\ReadableStorageFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentSecurityService
{
    public function secureStore(UploadedFile $file, string $directory = 'documents'): array
    {
        $this->assertBasicSafety($file);

        $disk = (string) config('security.uploads.disk', 'local');
        $originalName = $file->getClientOriginalName();
        $sanitizedOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'document';
        $filename = Str::uuid()->toString().'_'.$sanitizedOriginal;
        $path = $file->storeAs($directory, $filename, $disk);
        $scanMode = $this->scanMode();
        $scanStatus = 'clean';
        $scanRequestedAt = null;
        $scannedAt = now();
        $scanMessage = null;

        if ($this->scanEnabled()) {
            if ($scanMode === 'inline') {
                $this->scanForMalware($file);
            } elseif ($scanMode === 'quarantine') {
                $scanStatus = 'pending_scan';
                $scanRequestedAt = now();
                $scannedAt = null;
                $scanMessage = 'Queued for malware scan.';
            }
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'file_name' => $filename,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'scan_status' => $scanStatus,
            'scan_message' => $scanMessage,
            'scan_requested_at' => $scanRequestedAt,
            'scanned_at' => $scannedAt,
        ];
    }

    public function queueStoredDocumentScan(Document $document): void
    {
        if (! $this->shouldQueueScan()) {
            return;
        }

        ScanStoredDocument::dispatch($document->id);
    }

    public function queueStoredDocumentScans(iterable $documentIds): void
    {
        if (! $this->shouldQueueScan()) {
            return;
        }

        foreach ($documentIds as $documentId) {
            ScanStoredDocument::dispatch((int) $documentId);
        }
    }

    public function scanStoredDocument(Document $document): void
    {
        if (! $this->scanEnabled()) {
            $document->forceFill([
                'scan_status' => 'clean',
                'scan_message' => null,
                'scanned_at' => now(),
            ])->save();

            return;
        }

        if (! $document->storage_disk || ! $document->file_path) {
            $document->forceFill([
                'scan_status' => 'failed_scan',
                'scan_message' => 'Stored document path is missing.',
                'scanned_at' => now(),
            ])->save();

            return;
        }

        try {
            $readableFile = ReadableStorageFile::fromDisk(
                (string) $document->storage_disk,
                (string) $document->file_path,
                'The stored document could not be read for malware scanning.'
            );
        } catch (\RuntimeException $exception) {
            $document->forceFill([
                'scan_status' => 'failed_scan',
                'scan_message' => Str::limit($exception->getMessage(), 1000),
                'scanned_at' => now(),
            ])->save();

            return;
        }

        try {
            $this->scanAbsolutePath($readableFile->path());

            $document->forceFill([
                'scan_status' => 'clean',
                'scan_message' => null,
                'scanned_at' => now(),
            ])->save();
        } catch (ValidationException $exception) {
            $document->forceFill([
                'scan_status' => 'failed_scan',
                'scan_message' => Str::limit((string) collect($exception->errors())->flatten()->first(), 1000),
                'scanned_at' => now(),
            ])->save();
        } finally {
            $readableFile->cleanup();
        }
    }

    public function assertSafe(UploadedFile $file): void
    {
        $this->assertBasicSafety($file);
        $this->scanForMalware($file);
    }

    protected function assertBasicSafety(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'documents' => 'One of the uploaded files is invalid. Please try again.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mimeType = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        $allowedExtensions = collect(config('security.uploads.allowed_extensions', []))
            ->map(fn ($value) => strtolower((string) $value))
            ->all();
        $allowedMimeTypes = collect(config('security.uploads.allowed_mime_types', []))
            ->map(fn ($value) => strtolower((string) $value))
            ->all();

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'documents' => 'Unsupported file type uploaded. Allowed extensions: '.implode(', ', $allowedExtensions).'.',
            ]);
        }

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'documents' => 'Unsupported file format uploaded. Please upload PDF, JPG, PNG, DOC, or DOCX files only.',
            ]);
        }
    }

    protected function scanForMalware(UploadedFile $file): void
    {
        if (! $this->scanEnabled()) {
            return;
        }

        $this->scanAbsolutePath((string) $file->getRealPath());
    }

    protected function scanAbsolutePath(string $absolutePath): void
    {
        if (! $this->scanEnabled()) {
            return;
        }

        $commandTemplate = trim((string) config('security.uploads.scan_command'));

        if ($commandTemplate === '') {
            throw ValidationException::withMessages([
                'documents' => 'Malware scanning is enabled, but no scanner command is configured.',
            ]);
        }

        $command = str_replace('{file}', escapeshellarg($absolutePath), $commandTemplate);
        $output = [];
        $exitCode = 1;
        @exec($command.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            Storage::disk((string) config('security.uploads.disk', 'local'));

            throw ValidationException::withMessages([
                'documents' => 'The uploaded file failed the security scan and was rejected.',
            ]);
        }
    }

    protected function shouldQueueScan(): bool
    {
        return $this->scanEnabled() && $this->scanMode() === 'quarantine';
    }

    protected function scanEnabled(): bool
    {
        return (bool) config('security.uploads.scan_enabled');
    }

    protected function scanMode(): string
    {
        $mode = strtolower((string) config('security.uploads.scan_mode', 'inline'));

        return in_array($mode, ['inline', 'quarantine'], true) ? $mode : 'inline';
    }
}
