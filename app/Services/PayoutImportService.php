<?php

namespace App\Services;

use App\Models\PayoutBatch;
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
    public function importBatch(UploadedFile $file, User $user, array $attributes = []): PayoutBatch
    {
        $storedFile = $file->store('payout-uploads', 'local');
        $rows = $this->readSpreadsheet(Storage::disk('local')->path($storedFile));
        $cleanRows = collect($rows)
            ->map(fn (array $row, int $index) => $this->mapRow($row, $index + 1, (string) ($attributes['sector_label'] ?? '')))
            ->filter(fn (array $row) => $row['full_name'] !== '')
            ->values();

        if ($cleanRows->isEmpty()) {
            throw new \RuntimeException('No valid payout rows were found in the uploaded file.');
        }

        return DB::transaction(function () use ($file, $user, $attributes, $storedFile, $cleanRows) {
            $batch = PayoutBatch::create([
                'user_id' => $user->id,
                'bulk_deduplication_run_id' => $attributes['bulk_deduplication_run_id'] ?? null,
                'access_role' => $user->role,
                'batch_name' => trim((string) $attributes['batch_name']),
                'sector_label' => trim((string) $attributes['sector_label']),
                'venue' => trim((string) $attributes['venue']),
                'payout_amount' => $attributes['payout_amount'],
                'payout_date' => $attributes['payout_date'] ?? null,
                'source_filename' => $file->getClientOriginalName(),
                'upload_disk' => 'local',
                'upload_path' => $storedFile,
                'notes' => filled($attributes['notes'] ?? null) ? trim((string) $attributes['notes']) : null,
                'summary' => [
                    'total_entries' => $cleanRows->count(),
                    'pending_count' => $cleanRows->count(),
                    'paid_count' => 0,
                    'absent_count' => 0,
                    'deferred_count' => 0,
                ],
            ]);

            $batch->entries()->createMany($cleanRows->map(fn (array $row) => [
                'sequence_no' => $row['sequence_no'],
                'reference_no' => $row['reference_no'],
                'full_name' => $row['full_name'],
                'last_name' => $row['last_name'],
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'],
                'extension_name' => $row['extension_name'],
                'birthdate' => $row['birthdate'],
                'sector_label' => $row['sector_label'],
                'assistance_subtype' => $row['assistance_subtype'],
                'assistance_detail' => $row['assistance_detail'],
                'payout_status' => 'pending',
                'remarks' => $row['remarks'],
                'raw_row' => $row['raw_row'],
            ])->all());

            return $batch->loadCount('entries');
        });
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

    protected function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = [];
        $rows = [];

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
                $rows[] = $item;
            }
        }

        return $rows;
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
