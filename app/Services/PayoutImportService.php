<?php

namespace App\Services;

use App\Models\PayoutBatch;
use App\Support\ReadableStorageFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class PayoutImportService
{
    public function queueBatch(UploadedFile $file, User $user, array $attributes = []): PayoutBatch
    {
        $uploadDisk = (string) config('security.workflow_uploads.disk', 'local');
        $storedFile = $file->store('payout-uploads', $uploadDisk);

        return PayoutBatch::create([
            'user_id' => $user->id,
            'bulk_deduplication_run_id' => $attributes['bulk_deduplication_run_id'] ?? null,
            'access_role' => $user->role,
            'batch_name' => trim((string) $attributes['batch_name']),
            'sector_label' => trim((string) $attributes['sector_label']),
            'venue' => trim((string) $attributes['venue']),
            'payout_amount' => $attributes['payout_amount'],
            'payout_date' => $attributes['payout_date'] ?? null,
            'source_filename' => $file->getClientOriginalName(),
            'upload_disk' => $uploadDisk,
            'upload_path' => $storedFile,
            'import_status' => 'queued',
            'processed_rows' => 0,
            'progress_message' => 'Queued for payout import.',
            'notes' => filled($attributes['notes'] ?? null) ? trim((string) $attributes['notes']) : null,
            'summary' => [
                'total_entries' => 0,
                'pending_count' => 0,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
        ]);
    }

    public function processBatch(PayoutBatch $batch): PayoutBatch
    {
        if ($batch->import_status === 'completed' && $batch->entries()->exists()) {
            return $batch;
        }

        $batch->forceFill([
            'import_status' => 'processing',
            'processed_rows' => 0,
            'progress_message' => 'Reading payout spreadsheet...',
            'error_message' => null,
            'import_started_at' => now(),
            'import_completed_at' => null,
            'import_failed_at' => null,
        ])->save();

        $storedFile = $this->resolveStoredFile($batch->upload_disk, $batch->upload_path);

        try {
            $summary = $this->importRowsIntoBatch($batch, $storedFile->path());
        } finally {
            $storedFile->cleanup();
        }

        $batch->forceFill([
            'summary' => $summary,
            'import_status' => 'completed',
            'progress_message' => 'Payout batch import completed.',
            'error_message' => null,
            'processed_rows' => $summary['total_entries'],
            'import_completed_at' => now(),
            'import_failed_at' => null,
        ])->save();

        return $batch->fresh(['user']);
    }

    public function markBatchFailed(PayoutBatch $batch, string $message): void
    {
        $batch->forceFill([
            'import_status' => 'failed',
            'progress_message' => 'Payout batch import failed.',
            'error_message' => Str::limit($message, 1000),
            'import_failed_at' => now(),
        ])->save();
    }

    public function recomputeSummary(PayoutBatch $batch): array
    {
        $base = $batch->entries()
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw("SUM(CASE WHEN payout_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN payout_status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->selectRaw("SUM(CASE WHEN payout_status = 'absent' THEN 1 ELSE 0 END) as absent_count")
            ->selectRaw("SUM(CASE WHEN payout_status = 'deferred' THEN 1 ELSE 0 END) as deferred_count")
            ->first();

        return [
            'total_entries' => (int) ($base?->total_entries ?? 0),
            'pending_count' => (int) ($base?->pending_count ?? 0),
            'paid_count' => (int) ($base?->paid_count ?? 0),
            'absent_count' => (int) ($base?->absent_count ?? 0),
            'deferred_count' => (int) ($base?->deferred_count ?? 0),
        ];
    }

    protected function importRowsIntoBatch(PayoutBatch $batch, string $path): array
    {
        $summary = [
            'total_entries' => 0,
            'pending_count' => 0,
            'paid_count' => 0,
            'absent_count' => 0,
            'deferred_count' => 0,
        ];

        DB::transaction(function () use ($batch, $path, &$summary) {
            $batch->entries()->delete();

            $sequenceNo = 0;
            $buffer = [];
            $timestamp = now();

            $this->streamSpreadsheetRows($path, function (array $row, int $rowNumber) use ($batch, &$sequenceNo, &$buffer, &$summary, $timestamp) {
                $mapped = $this->mapRow($row, $sequenceNo + 1, (string) $batch->sector_label);

                if ($mapped['full_name'] === '') {
                    return;
                }

                $sequenceNo++;
                $mapped['sequence_no'] = $sequenceNo;

                $buffer[] = [
                    'payout_batch_id' => $batch->id,
                    'sequence_no' => $mapped['sequence_no'],
                    'reference_no' => $mapped['reference_no'],
                    'full_name' => $mapped['full_name'],
                    'last_name' => $mapped['last_name'],
                    'first_name' => $mapped['first_name'],
                    'middle_name' => $mapped['middle_name'],
                    'extension_name' => $mapped['extension_name'],
                    'birthdate' => $mapped['birthdate'],
                    'sector_label' => $mapped['sector_label'],
                    'assistance_subtype' => $mapped['assistance_subtype'],
                    'assistance_detail' => $mapped['assistance_detail'],
                    'payout_status' => 'pending',
                    'remarks' => $mapped['remarks'],
                    'raw_row' => json_encode($mapped['raw_row'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                $summary['total_entries']++;
                $summary['pending_count']++;

                if (count($buffer) >= 500) {
                    $this->flushEntryBuffer($buffer);
                    $this->updateBatchProgress($batch, $summary['total_entries']);
                }
            });

            $this->flushEntryBuffer($buffer);
            $this->updateBatchProgress($batch, $summary['total_entries']);
        });

        if ($summary['total_entries'] === 0) {
            throw new \RuntimeException('No valid payout rows were found in the uploaded file.');
        }

        return $summary;
    }

    protected function flushEntryBuffer(array &$buffer): void
    {
        if ($buffer === []) {
            return;
        }

        DB::table('payout_entries')->insert($buffer);
        $buffer = [];
    }

    protected function updateBatchProgress(PayoutBatch $batch, int $processedRows): void
    {
        $batch->forceFill([
            'processed_rows' => $processedRows,
            'progress_message' => $processedRows === 0
                ? 'Reading payout spreadsheet...'
                : 'Imported '.$processedRows.' payout rows...',
        ])->save();
    }

    protected function streamSpreadsheetRows(string $path, callable $onRow): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestDataRow();
            $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

            $headers = [];

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $headers[$column] = $this->normalizeHeader((string) $sheet->getCell([$column, 1])->getValue());
            }

            for ($row = 2; $row <= $highestRow; $row++) {
                $item = [];
                $hasData = false;

                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $header = $headers[$column] ?? null;

                    if (! $header) {
                        continue;
                    }

                    $value = $sheet->getCell([$column, $row])->getValue();

                    if ($value !== null && $value !== '') {
                        $hasData = true;
                    }

                    $item[$header] = $this->normalizeCellValue($value, $header);
                }

                if ($hasData) {
                    $onRow($item, $row);
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    protected function resolveStoredFile(string $disk, string $path): ReadableStorageFile
    {
        return ReadableStorageFile::fromDisk($disk, $path, 'The uploaded payout file could not be found.');
    }

    protected function normalizeHeader(string $header): ?string
    {
        $normalized = Str::of($header)
            ->lower()
            ->replace(['.', '-', '/', '\\', '(', ')'], ' ')
            ->replace('&', ' and ')
            ->squish()
            ->replace(' ', '_')
            ->value();

        if ($normalized === '') {
            return null;
        }

        return [
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'firstname' => 'first_name',
            'middlename' => 'middle_name',
            'middle_initial' => 'middle_name',
            'extension' => 'extension_name',
            'ext' => 'extension_name',
            'birth_date' => 'birthdate',
            'date_of_birth' => 'birthdate',
            'dob' => 'birthdate',
            'name' => 'full_name',
            'client_name' => 'full_name',
            'beneficiary_name' => 'full_name',
            'sector' => 'sector_label',
            'sector_label' => 'sector_label',
            'category' => 'sector_label',
            'service_category' => 'assistance_subtype',
            'assistance_subtype' => 'assistance_subtype',
            'assistance_detail' => 'assistance_detail',
            'reference_number' => 'reference_no',
            'reference_no' => 'reference_no',
            'remarks' => 'remarks',
        ][$normalized] ?? $normalized;
    }

    protected function normalizeCellValue(mixed $value, string $header): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($header === 'birthdate') {
            return $this->normalizeBirthdate($value);
        }

        return is_string($value) ? trim($value) : $value;
    }

    protected function normalizeBirthdate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(SpreadsheetDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function mapRow(array $row, int $sequenceNo, string $defaultSector): array
    {
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $middleName = trim((string) ($row['middle_name'] ?? ''));
        $extensionName = trim((string) ($row['extension_name'] ?? ''));
        $fullName = trim((string) ($row['full_name'] ?? ''));

        if ($fullName === '') {
            $fullName = trim(collect([$firstName, $middleName, $lastName, $extensionName])->filter()->implode(' '));
        }

        return [
            'sequence_no' => $sequenceNo,
            'reference_no' => trim((string) ($row['reference_no'] ?? '')) ?: null,
            'full_name' => $fullName,
            'last_name' => $lastName !== '' ? $lastName : null,
            'first_name' => $firstName !== '' ? $firstName : null,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'extension_name' => $extensionName !== '' ? $extensionName : null,
            'birthdate' => $row['birthdate'] ?? null,
            'sector_label' => trim((string) ($row['sector_label'] ?? '')) ?: $defaultSector,
            'assistance_subtype' => trim((string) ($row['assistance_subtype'] ?? '')) ?: null,
            'assistance_detail' => trim((string) ($row['assistance_detail'] ?? '')) ?: null,
            'remarks' => trim((string) ($row['remarks'] ?? '')) ?: null,
            'raw_row' => $row,
        ];
    }
}
