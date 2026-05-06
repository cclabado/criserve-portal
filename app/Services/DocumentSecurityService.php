<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentSecurityService
{
    public function secureStore(UploadedFile $file, string $directory = 'documents'): array
    {
        $this->assertSafe($file);

        $disk = (string) config('security.uploads.disk', 'local');
        $originalName = $file->getClientOriginalName();
        $sanitizedOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'document';
        $filename = Str::uuid()->toString().'_'.$sanitizedOriginal;
        $path = $file->storeAs($directory, $filename, $disk);

        return [
            'disk' => $disk,
            'path' => $path,
            'file_name' => $filename,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    public function assertSafe(UploadedFile $file): void
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

        $this->scanForMalware($file);
    }

    protected function scanForMalware(UploadedFile $file): void
    {
        if (! config('security.uploads.scan_enabled')) {
            return;
        }

        $commandTemplate = trim((string) config('security.uploads.scan_command'));

        if ($commandTemplate === '') {
            throw ValidationException::withMessages([
                'documents' => 'Malware scanning is enabled, but no scanner command is configured.',
            ]);
        }

        $command = str_replace('{file}', escapeshellarg($file->getRealPath()), $commandTemplate);
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
}
