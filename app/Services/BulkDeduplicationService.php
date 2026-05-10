<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceSubtype;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProfile;
use App\Models\BulkDeduplicationRun;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class BulkDeduplicationService
{
    public function __construct(
        protected FrequencyEligibilityService $frequencyEligibility
    ) {
    }

    public function queueUpload(
        UploadedFile $file,
        User $user,
        array $options = [],
        ?UploadedFile $referenceFile = null
    ): BulkDeduplicationRun
    {
        $storedFile = $file->store('deduplication-uploads', 'local');
        $storedReferenceFile = $referenceFile?->store('deduplication-uploads', 'local');
        $compareSource = $options['compare_source'] ?? 'system';
        $applyFrequencyRules = (bool) ($options['apply_frequency_rules'] ?? false);

        return BulkDeduplicationRun::create([
            'user_id' => $user->id,
            'access_role' => $user->role,
            'original_filename' => $file->getClientOriginalName(),
            'upload_disk' => 'local',
            'upload_path' => $storedFile,
            'reference_upload_disk' => $storedReferenceFile ? 'local' : null,
            'reference_upload_path' => $storedReferenceFile,
            'status' => 'queued',
            'progress_percentage' => 0,
            'progress_message' => 'Waiting for background processor...',
            'summary' => [
                'total_rows' => 0,
                'clean_count' => 0,
                'duplicate_count' => 0,
                'finding_count' => 0,
                'skipped_count' => 0,
                'compare_source' => $compareSource,
                'apply_frequency_rules' => $applyFrequencyRules,
                'reference_filename' => $referenceFile?->getClientOriginalName(),
            ],
            'clean_rows' => [],
            'duplicate_rows' => [],
            'finding_rows' => [],
            'skipped_rows' => [],
        ]);
    }

    public function processRun(BulkDeduplicationRun $run): BulkDeduplicationRun
    {
        $run->refresh();
        $run->forceFill([
            'status' => 'processing',
            'progress_percentage' => max(5, (int) $run->progress_percentage),
            'progress_message' => 'Preparing upload for deduplication...',
            'error_message' => null,
            'started_at' => $run->started_at ?: now(),
            'completed_at' => null,
            'failed_at' => null,
        ])->save();

        $uploadPath = $this->resolveStoredFilePath($run->upload_disk, $run->upload_path);
        $referenceUploadPath = $run->reference_upload_path
            ? $this->resolveStoredFilePath($run->reference_upload_disk, $run->reference_upload_path)
            : null;

        $options = $run->summary ?? [];
        $rows = $this->readSpreadsheet($uploadPath);
        $compareSource = $options['compare_source'] ?? 'system';
        $applyFrequencyRules = (bool) ($options['apply_frequency_rules'] ?? false);

        $this->updateRunProgress($run, 12, 'Loading comparison records...', [
            'total_rows' => count($rows),
        ]);

        $systemReference = $this->loadReferenceRecords();
        $systemReferenceIndex = $this->buildReferenceIndex($systemReference);
        $reference = $compareSource === 'uploaded_list'
            ? $this->loadReferenceRowsFromStoredFile($referenceUploadPath)
            : $systemReference;
        $referenceIndex = $compareSource === 'uploaded_list'
            ? $this->buildReferenceIndex($reference)
            : $systemReferenceIndex;
        $mappings = $this->loadAssistanceMappings();
        $cleanRows = [];
        $duplicateRows = [];
        $findingRows = [];
        $skippedRows = [];
        $uploadedExactKeys = [];

        $totalRows = max(count($rows), 1);

        foreach ($rows as $index => $row) {
            $prepared = $this->prepareUploadedRow($row);

            if ($prepared['has_missing_required']) {
                $skippedRows[] = [
                    'row_number' => $prepared['row_number'],
                    'reason' => 'Missing required fields: last name, first name, and birthdate are required.',
                    'row' => $prepared['display'],
                ];
                $this->touchProgressWithinLoop($run, $index + 1, $totalRows);

                continue;
            }

            $internalKey = $prepared['exact_key'];

            if (isset($uploadedExactKeys[$internalKey])) {
                $duplicateRows[] = $this->buildDuplicateRow(
                    $prepared,
                    'duplicate_upload',
                    'Duplicate of uploaded row '.$uploadedExactKeys[$internalKey].'.'
                );
                $this->touchProgressWithinLoop($run, $index + 1, $totalRows);
                continue;
            }

            $uploadedExactKeys[$internalKey] = $prepared['row_number'];

            $exactMatch = $this->findExactMatch($prepared, $referenceIndex);
            $systemExactMatch = $this->findExactMatch($prepared, $systemReferenceIndex);
            $frequency = $applyFrequencyRules
                ? $this->evaluateFrequency($prepared, $systemExactMatch, $mappings)
                : $this->frequencyNotApplied();

            if ($exactMatch) {
                $duplicateRows[] = $this->buildDuplicateRow(
                    $prepared,
                    $compareSource === 'uploaded_list' ? 'duplicate_uploaded_list' : 'duplicate_database',
                    'Exact match found in '.$exactMatch['source_label'].'.',
                    $exactMatch,
                    $frequency
                );
                $this->touchProgressWithinLoop($run, $index + 1, $totalRows);
                continue;
            }

            if (($frequency['status'] ?? 'eligible') !== 'eligible') {
                $duplicateRows[] = $this->buildDuplicateRow(
                    $prepared,
                    'frequency_not_eligible',
                    $frequency['message'] ?? 'Frequency rule check marked this row as not eligible.',
                    null,
                    $frequency
                );
                $this->touchProgressWithinLoop($run, $index + 1, $totalRows);
                continue;
            }

            $possibleMatches = $this->findPossibleMatches($prepared, $referenceIndex);

            $cleanRows[] = [
                'row_number' => $prepared['row_number'],
                ...$prepared['display'],
                'frequency_status' => $frequency['status'] ?? 'eligible',
                'frequency_message' => $frequency['message'] ?? 'Eligible for inclusion.',
                'assistance_subtype' => $frequency['resolved_assistance_subtype'] ?? '',
                'assistance_detail' => $frequency['resolved_assistance_detail'] ?? '',
            ];

            if ($possibleMatches !== []) {
                $findingRows[] = [
                    'row_number' => $prepared['row_number'],
                    ...$prepared['display'],
                    'finding_type' => 'possible_duplicate',
                    'finding_message' => 'Possible duplicate found due to close name/date match.',
                    'matches' => $possibleMatches,
                ];
            }

            $this->touchProgressWithinLoop($run, $index + 1, $totalRows);
        }

        $run->forceFill([
            'status' => 'completed',
            'progress_percentage' => 100,
            'progress_message' => 'Deduplication complete. Results are ready for review.',
            'completed_at' => now(),
            'summary' => [
                'total_rows' => count($rows),
                'clean_count' => count($cleanRows),
                'duplicate_count' => count($duplicateRows),
                'finding_count' => count($findingRows),
                'skipped_count' => count($skippedRows),
                'compare_source' => $compareSource,
                'apply_frequency_rules' => $applyFrequencyRules,
                'reference_filename' => $options['reference_filename'] ?? null,
            ],
            'clean_rows' => $cleanRows,
            'duplicate_rows' => $duplicateRows,
            'finding_rows' => $findingRows,
            'skipped_rows' => $skippedRows,
        ])->save();

        return $run;
    }

    public function exportRows(BulkDeduplicationRun $run, string $type): array
    {
        return match ($type) {
            'clean' => $run->clean_rows ?? [],
            'duplicates' => $run->duplicate_rows ?? [],
            'findings' => $run->finding_rows ?? [],
            default => [],
        };
    }

    protected function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        $rows = [];

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $headers[$column] = $this->normalizeHeader((string) $sheet->getCell([$column, 1])->getValue());
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $item = ['row_number' => $row];
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
            'last_name' => 'last_name',
            'surname' => 'last_name',
            'firstname' => 'first_name',
            'first_name' => 'first_name',
            'middlename' => 'middle_name',
            'middle_name' => 'middle_name',
            'middle_initial' => 'middle_name',
            'ext' => 'extension_name',
            'ext_name' => 'extension_name',
            'extension' => 'extension_name',
            'extension_name' => 'extension_name',
            'birth_date' => 'birthdate',
            'date_of_birth' => 'birthdate',
            'dob' => 'birthdate',
            'birthdate' => 'birthdate',
            'assistance_subtype' => 'assistance_subtype',
            'assistance_subtype_id' => 'assistance_subtype_id',
            'assistance_detail' => 'assistance_detail',
            'assistance_detail_id' => 'assistance_detail_id',
            'frequency_subject' => 'frequency_subject',
            'frequency_case_key' => 'frequency_case_key',
            'case_key' => 'frequency_case_key',
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

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
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
            return trim((string) $value) !== '' ? trim((string) $value) : null;
        }
    }

    protected function prepareUploadedRow(array $row): array
    {
        $display = [
            'last_name' => trim((string) ($row['last_name'] ?? '')),
            'first_name' => trim((string) ($row['first_name'] ?? '')),
            'middle_name' => trim((string) ($row['middle_name'] ?? '')),
            'extension_name' => trim((string) ($row['extension_name'] ?? '')),
            'birthdate' => trim((string) ($row['birthdate'] ?? '')),
            'reference_no' => trim((string) ($row['reference_no'] ?? '')),
            'remarks' => trim((string) ($row['remarks'] ?? '')),
        ];

        $normalizedBirthdate = $this->normalizeBirthdate($display['birthdate']);

        return [
            'row_number' => $row['row_number'],
            'display' => $display,
            'raw' => $row,
            'normalized' => [
                'last_name' => $this->normalizeName((string) ($row['last_name'] ?? '')),
                'first_name' => $this->normalizeName((string) ($row['first_name'] ?? '')),
                'middle_name' => $this->normalizeName((string) ($row['middle_name'] ?? '')),
                'extension_name' => $this->normalizeName((string) ($row['extension_name'] ?? '')),
                'birthdate' => $normalizedBirthdate,
            ],
            'exact_key' => implode('|', [
                $this->normalizeName((string) ($row['last_name'] ?? '')),
                $this->normalizeName((string) ($row['first_name'] ?? '')),
                $this->normalizeName((string) ($row['middle_name'] ?? '')),
                $this->normalizeName((string) ($row['extension_name'] ?? '')),
                $normalizedBirthdate,
            ]),
            'has_missing_required' => $this->normalizeName((string) ($row['last_name'] ?? '')) === ''
                || $this->normalizeName((string) ($row['first_name'] ?? '')) === ''
                || blank($normalizedBirthdate),
        ];
    }

    protected function normalizeName(string $value): string
    {
        $normalized = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->trim()
            ->value();

        return $normalized;
    }

    protected function loadReferenceRecords(): Collection
    {
        $clientRecords = Client::query()
            ->select(['id', 'last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate'])
            ->get()
            ->map(function (Client $client) {
            return $this->mapReferenceRecord(
                'client',
                $client->id,
                'Client database',
                $client->last_name,
                $client->first_name,
                $client->middle_name,
                $client->extension_name,
                $client->birthdate,
                ['client_id' => $client->id]
            );
        });

        $profileRecords = BeneficiaryProfile::query()
            ->select(['id', 'last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate'])
            ->get()
            ->map(function (BeneficiaryProfile $profile) {
            return $this->mapReferenceRecord(
                'beneficiary_profile',
                $profile->id,
                'Beneficiary profile database',
                $profile->last_name,
                $profile->first_name,
                $profile->middle_name,
                $profile->extension_name,
                $profile->birthdate,
                ['beneficiary_profile_id' => $profile->id]
            );
        });

        $beneficiaryRecords = Beneficiary::query()
            ->with('application:id,beneficiary_profile_id')
            ->select(['id', 'application_id', 'beneficiary_profile_id', 'last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate'])
            ->get()
            ->map(function (Beneficiary $beneficiary) {
                return $this->mapReferenceRecord(
                    'beneficiary',
                    $beneficiary->id,
                    'Beneficiary database',
                    $beneficiary->last_name,
                    $beneficiary->first_name,
                    $beneficiary->middle_name,
                    $beneficiary->extension_name,
                    $beneficiary->birthdate,
                    [
                        'beneficiary_profile_id' => $beneficiary->beneficiary_profile_id ?: $beneficiary->application?->beneficiary_profile_id,
                    ]
                );
            });

        return $clientRecords
            ->concat($profileRecords)
            ->concat($beneficiaryRecords)
            ->values();
    }

    protected function mapReferenceRecord(
        string $sourceType,
        int $sourceId,
        string $sourceLabel,
        ?string $lastName,
        ?string $firstName,
        ?string $middleName,
        ?string $extensionName,
        mixed $birthdate,
        array $extra = []
    ): array {
        $normalizedBirthdate = $birthdate ? Carbon::parse($birthdate)->format('Y-m-d') : null;

        return [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_label' => $sourceLabel,
            'last_name' => $lastName ?? '',
            'first_name' => $firstName ?? '',
            'middle_name' => $middleName ?? '',
            'extension_name' => $extensionName ?? '',
            'birthdate' => $normalizedBirthdate,
            'exact_key' => implode('|', [
                $this->normalizeName((string) $lastName),
                $this->normalizeName((string) $firstName),
                $this->normalizeName((string) $middleName),
                $this->normalizeName((string) $extensionName),
                $normalizedBirthdate,
            ]),
            'normalized' => [
                'last_name' => $this->normalizeName((string) $lastName),
                'first_name' => $this->normalizeName((string) $firstName),
                'middle_name' => $this->normalizeName((string) $middleName),
                'extension_name' => $this->normalizeName((string) $extensionName),
                'birthdate' => $normalizedBirthdate,
            ],
            ...$extra,
        ];
    }

    protected function findExactMatch(array $prepared, array $referenceIndex): ?array
    {
        return $referenceIndex['exact'][$prepared['exact_key']] ?? null;
    }

    protected function loadReferenceRowsFromStoredFile(?string $referencePath): Collection
    {
        if (! $referencePath) {
            return collect();
        }

        return collect($this->readSpreadsheet($referencePath))
            ->map(function (array $row) {
                $prepared = $this->prepareUploadedRow($row);

                return [
                    'source_type' => 'uploaded_reference',
                    'source_id' => $prepared['row_number'],
                    'source_label' => 'Uploaded comparison list',
                    'last_name' => $prepared['display']['last_name'],
                    'first_name' => $prepared['display']['first_name'],
                    'middle_name' => $prepared['display']['middle_name'],
                    'extension_name' => $prepared['display']['extension_name'],
                    'birthdate' => $prepared['display']['birthdate'],
                    'exact_key' => $prepared['exact_key'],
                    'normalized' => $prepared['normalized'],
                ];
            })
            ->filter(fn (array $row) => $row['exact_key'] !== '||||')
            ->values();
    }

    protected function findPossibleMatches(array $prepared, array $referenceIndex): array
    {
        $matches = [];
        $candidatePool = $this->findPossibleMatchCandidates($prepared, $referenceIndex);

        foreach ($candidatePool as $record) {
            if ($record['exact_key'] === $prepared['exact_key']) {
                continue;
            }

            if (! $this->isPossibleDateMatch($prepared['normalized']['birthdate'], $record['normalized']['birthdate'])) {
                continue;
            }

            $nameScore = $this->calculateNameSimilarityScore($prepared['normalized'], $record['normalized']);

            if ($nameScore < 0.84) {
                continue;
            }

            $matches[] = [
                'source' => $record['source_label'],
                'source_type' => $record['source_type'],
                'source_id' => $record['source_id'],
                'matched_name' => trim(implode(' ', array_filter([
                    $record['first_name'],
                    $record['middle_name'],
                    $record['last_name'],
                    $record['extension_name'],
                ]))),
                'matched_birthdate' => $record['birthdate'],
                'similarity_score' => round($nameScore * 100, 1).'%',
            ];
        }

        return array_slice($matches, 0, 5);
    }

    protected function buildReferenceIndex(Collection $reference): array
    {
        $exact = [];
        $byBirthdate = [];
        $byMonthDay = [];

        foreach ($reference as $record) {
            $exact[$record['exact_key']] ??= $record;

            if (! empty($record['birthdate'])) {
                $byBirthdate[$record['birthdate']][] = $record;

                try {
                    $date = Carbon::parse($record['birthdate']);
                    $byMonthDay[$date->format('m-d')][] = $record;
                } catch (\Throwable) {
                    // Ignore invalid reference dates for grouped candidate lookup.
                }
            }
        }

        return [
            'exact' => $exact,
            'by_birthdate' => $byBirthdate,
            'by_month_day' => $byMonthDay,
        ];
    }

    protected function findPossibleMatchCandidates(array $prepared, array $referenceIndex): array
    {
        $birthdate = $prepared['normalized']['birthdate'] ?? null;

        if (! $birthdate) {
            return [];
        }

        $candidates = [];

        try {
            $date = Carbon::parse($birthdate);

            for ($offset = -7; $offset <= 7; $offset++) {
                $candidateDate = $date->copy()->addDays($offset)->format('Y-m-d');

                foreach ($referenceIndex['by_birthdate'][$candidateDate] ?? [] as $record) {
                    $candidates[$record['source_type'].':'.$record['source_id']] = $record;
                }
            }

            $swappedDate = $date->format('Y-d-m');

            foreach ($referenceIndex['by_birthdate'][$swappedDate] ?? [] as $record) {
                $candidates[$record['source_type'].':'.$record['source_id']] = $record;
            }

            foreach ($referenceIndex['by_month_day'][$date->format('m-d')] ?? [] as $record) {
                $candidates[$record['source_type'].':'.$record['source_id']] = $record;
            }
        } catch (\Throwable) {
            foreach ($referenceIndex['by_birthdate'][$birthdate] ?? [] as $record) {
                $candidates[$record['source_type'].':'.$record['source_id']] = $record;
            }
        }

        return array_values($candidates);
    }

    protected function isPossibleDateMatch(?string $uploadDate, ?string $referenceDate): bool
    {
        if (! $uploadDate || ! $referenceDate) {
            return false;
        }

        if ($uploadDate === $referenceDate) {
            return true;
        }

        try {
            $uploaded = Carbon::parse($uploadDate);
            $reference = Carbon::parse($referenceDate);
        } catch (\Throwable) {
            return false;
        }

        if ($uploaded->diffInDays($reference) <= 7) {
            return true;
        }

        if ($uploaded->format('Y-d-m') === $reference->format('Y-m-d')) {
            return true;
        }

        return abs($uploaded->year - $reference->year) <= 1
            && $uploaded->format('m-d') === $reference->format('m-d');
    }

    protected function calculateNameSimilarityScore(array $uploaded, array $reference): float
    {
        $weights = [
            'last_name' => 0.35,
            'first_name' => 0.35,
            'middle_name' => 0.2,
            'extension_name' => 0.1,
        ];

        $score = 0.0;

        foreach ($weights as $field => $weight) {
            $score += $this->fieldSimilarity((string) ($uploaded[$field] ?? ''), (string) ($reference[$field] ?? '')) * $weight;
        }

        return $score;
    }

    protected function fieldSimilarity(string $left, string $right): float
    {
        if ($left === '' && $right === '') {
            return 1.0;
        }

        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 1.0;
        }

        similar_text($left, $right, $percent);
        $normalized = $percent / 100;
        $levenshteinScore = 1 - (levenshtein($left, $right) / max(strlen($left), strlen($right), 1));

        return max($normalized, $levenshteinScore);
    }

    protected function loadAssistanceMappings(): array
    {
        return [
            'subtypes_by_id' => AssistanceSubtype::query()->pluck('name', 'id')->all(),
            'subtypes_by_name' => AssistanceSubtype::query()
                ->get()
                ->mapWithKeys(fn (AssistanceSubtype $subtype) => [$this->normalizeName($subtype->name) => $subtype])
                ->all(),
            'details_by_id' => AssistanceDetail::query()->pluck('name', 'id')->all(),
            'details_by_name' => AssistanceDetail::query()
                ->get()
                ->mapWithKeys(fn (AssistanceDetail $detail) => [$this->normalizeName($detail->name) => $detail])
                ->all(),
        ];
    }

    protected function evaluateFrequency(array $prepared, ?array $exactMatch, array $mappings): array
    {
        $subtypeId = $this->resolveSubtypeId($prepared['raw'], $mappings);
        $detailId = $this->resolveDetailId($prepared['raw'], $mappings);

        $result = [
            'status' => 'eligible',
            'message' => $subtypeId || $detailId
                ? 'No released application conflict was found for the supplied assistance context.'
                : 'Eligible. No assistance subtype/detail was supplied for frequency evaluation.',
            'resolved_assistance_subtype' => $subtypeId ? ($mappings['subtypes_by_id'][$subtypeId] ?? '') : '',
            'resolved_assistance_detail' => $detailId ? ($mappings['details_by_id'][$detailId] ?? '') : '',
            'basis_application_id' => null,
            'basis_reference_no' => null,
        ];

        if (! $subtypeId || ! $exactMatch) {
            return $result;
        }

        $payload = [
            'assistance_subtype_id' => $subtypeId,
            'assistance_detail_id' => $detailId,
            'frequency_subject' => $this->resolveFrequencySubject($prepared['raw'], $exactMatch),
            'frequency_case_key' => trim((string) ($prepared['raw']['frequency_case_key'] ?? '')),
            'frequency_override_reason' => '',
        ];

        if ($payload['frequency_subject'] === 'beneficiary' && ! empty($exactMatch['beneficiary_profile_id'])) {
            $payload['beneficiary_profile_id'] = $exactMatch['beneficiary_profile_id'];
        } elseif (! empty($exactMatch['client_id'])) {
            $payload['client_id'] = $exactMatch['client_id'];
            $payload['frequency_subject'] = 'client';
        } else {
            return $result;
        }

        $frequencyResult = $this->frequencyEligibility->evaluate($payload);
        $basisApplication = ! empty($frequencyResult['basis_application_id'])
            ? Application::query()->find($frequencyResult['basis_application_id'])
            : null;

        return [
            'status' => $frequencyResult['status'] ?? 'eligible',
            'message' => $frequencyResult['message'] ?? $result['message'],
            'resolved_assistance_subtype' => $result['resolved_assistance_subtype'],
            'resolved_assistance_detail' => $result['resolved_assistance_detail'],
            'basis_application_id' => $basisApplication?->id,
            'basis_reference_no' => $basisApplication?->reference_no,
        ];
    }

    protected function frequencyNotApplied(): array
    {
        return [
            'status' => 'not_applied',
            'message' => 'Frequency rules were not applied for this run.',
            'resolved_assistance_subtype' => '',
            'resolved_assistance_detail' => '',
            'basis_application_id' => null,
            'basis_reference_no' => null,
        ];
    }

    protected function resolveSubtypeId(array $row, array $mappings): ?int
    {
        if (! empty($row['assistance_subtype_id']) && is_numeric($row['assistance_subtype_id'])) {
            return (int) $row['assistance_subtype_id'];
        }

        if (! empty($row['assistance_subtype'])) {
            $match = $mappings['subtypes_by_name'][$this->normalizeName((string) $row['assistance_subtype'])] ?? null;

            return $match?->id;
        }

        return null;
    }

    protected function resolveDetailId(array $row, array $mappings): ?int
    {
        if (! empty($row['assistance_detail_id']) && is_numeric($row['assistance_detail_id'])) {
            return (int) $row['assistance_detail_id'];
        }

        if (! empty($row['assistance_detail'])) {
            $match = $mappings['details_by_name'][$this->normalizeName((string) $row['assistance_detail'])] ?? null;

            return $match?->id;
        }

        return null;
    }

    protected function resolveFrequencySubject(array $row, array $exactMatch): string
    {
        $requestedSubject = strtolower(trim((string) ($row['frequency_subject'] ?? '')));

        if (in_array($requestedSubject, ['client', 'beneficiary'], true)) {
            return $requestedSubject;
        }

        return in_array($exactMatch['source_type'], ['beneficiary_profile', 'beneficiary'], true)
            ? 'beneficiary'
            : 'client';
    }

    protected function buildDuplicateRow(
        array $prepared,
        string $type,
        string $reason,
        ?array $match = null,
        ?array $frequency = null
    ): array {
        return [
            'row_number' => $prepared['row_number'],
            ...$prepared['display'],
            'duplicate_type' => $type,
            'duplicate_reason' => $reason,
            'matched_source' => $match['source_label'] ?? '',
            'matched_source_type' => $match['source_type'] ?? '',
            'matched_source_id' => $match['source_id'] ?? '',
            'matched_birthdate' => $match['birthdate'] ?? '',
            'frequency_status' => $frequency['status'] ?? '',
            'frequency_message' => $frequency['message'] ?? '',
            'basis_reference_no' => $frequency['basis_reference_no'] ?? '',
        ];
    }

    protected function resolveStoredFilePath(?string $disk, ?string $path): string
    {
        if (! $disk || ! $path || ! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('The uploaded deduplication file could not be found.');
        }

        return Storage::disk($disk)->path($path);
    }

    protected function touchProgressWithinLoop(BulkDeduplicationRun $run, int $processedRows, int $totalRows): void
    {
        if ($processedRows < $totalRows && $processedRows % 25 !== 0) {
            return;
        }

        $progress = 15 + (int) floor(($processedRows / max($totalRows, 1)) * 80);

        $this->updateRunProgress(
            $run,
            min(95, $progress),
            'Processing row '.$processedRows.' of '.$totalRows.'...'
        );
    }

    protected function updateRunProgress(BulkDeduplicationRun $run, int $progress, string $message, array $summaryUpdates = []): void
    {
        $summary = array_merge($run->summary ?? [], $summaryUpdates);

        $run->forceFill([
            'progress_percentage' => max(0, min(100, $progress)),
            'progress_message' => $message,
            'summary' => $summary,
        ])->save();
    }
}
