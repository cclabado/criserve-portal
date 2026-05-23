<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$outputDir = __DIR__.'/../storage/app/deduplication-samples';
$uploadCount = max(1, (int) ($argv[1] ?? 200));
$referenceCount = max(1, (int) ($argv[2] ?? 300));

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

mt_srand(20260512);

$lastNames = [
    'Santos', 'Reyes', 'Dela Cruz', 'Garcia', 'Mendoza', 'Torres', 'Gonzales', 'Gonzalez',
    'Bautista', 'Rivera', 'Aquino', 'Flores', 'Castillo', 'Navarro', 'Domingo', 'Mercado',
    'Ramos', 'Fernandez', 'Villanueva', 'Padilla', 'Salazar', 'Rosales', 'Diaz', 'Lopez',
    'Cruz', 'Herrera', 'Soriano', 'Andres', 'Panganiban', 'Maliksi',
];

$firstNames = [
    'Juan', 'Maria', 'Pedro', 'Ana', 'Jose', 'Liza', 'Carlo', 'Mika', 'Rogelio', 'Sofia',
    'Mark', 'Angela', 'Paolo', 'Cherry', 'Jessa', 'Luis', 'Nina', 'Marco', 'Ella', 'Rina',
    'Miguel', 'Clarissa', 'Romeo', 'Elena', 'Victor', 'Marlon', 'Grace', 'Ivy', 'Rafael', 'Mae',
];

$middleNames = ['Cruz', 'Lopez', 'Reyes', 'Santos', 'Garcia', 'Torres', 'Rivera', 'Diaz', '', '', ''];
$extensions = ['', '', '', 'Jr', 'Sr', 'III'];

$assistanceMatrix = [
    ['subtype' => 'Medical Assistance', 'detail' => 'Hospital Bill'],
    ['subtype' => 'Medical Assistance', 'detail' => 'Laboratory'],
    ['subtype' => 'Transportation Assistance', 'detail' => 'Bus Fare'],
    ['subtype' => 'Transportation Assistance', 'detail' => 'Fuel Support'],
    ['subtype' => 'Burial Assistance', 'detail' => 'Funeral Services'],
    ['subtype' => 'Food Assistance', 'detail' => 'Relief Pack'],
    ['subtype' => 'Cash Relief Assistance', 'detail' => 'Emergency Cash'],
];

$remarksPool = [
    'Priority row for validation',
    'Uploaded during field intake',
    'Expected to remain clean',
    'Use for duplicate check',
    'Potential overlap with comparison list',
    'Frequency eligibility sample row',
    'Good control sample',
];

$uploadHeader = ['last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate', 'assistance_subtype', 'assistance_detail', 'frequency_subject', 'frequency_case_key', 'reference_no', 'remarks'];
$referenceHeader = ['last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate', 'reference_no', 'remarks'];

$uploadRows = [$uploadHeader];
$referenceRows = [$referenceHeader];
$uploadProfiles = [];
$exactDuplicateCount = min($uploadCount, max(20, (int) floor($referenceCount * 0.2)));
$fuzzyDuplicateCount = min(
    max(0, $uploadCount - $exactDuplicateCount),
    max(15, (int) floor($referenceCount * 0.1))
);

for ($i = 1; $i <= $uploadCount; $i++) {
    $lastName = $lastNames[array_rand($lastNames)];
    $firstName = $firstNames[array_rand($firstNames)];
    $middleName = $middleNames[array_rand($middleNames)];
    $extension = $extensions[array_rand($extensions)];
    $birthdate = sprintf(
        '%04d-%02d-%02d',
        mt_rand(1970, 2004),
        mt_rand(1, 12),
        mt_rand(1, 28)
    );
    $assistance = $assistanceMatrix[array_rand($assistanceMatrix)];
    $frequencySubject = mt_rand(0, 1) === 1 ? 'client' : 'beneficiary';
    $caseKey = strtolower(str_replace(' ', '-', $assistance['subtype'])).'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
    $referenceNo = 'UPL-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
    $remarks = $remarksPool[array_rand($remarksPool)];

    $uploadProfiles[] = [
        'last_name' => $lastName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'extension_name' => $extension,
        'birthdate' => $birthdate,
        'reference_no' => $referenceNo,
        'assistance_subtype' => $assistance['subtype'],
        'assistance_detail' => $assistance['detail'],
    ];

    $uploadRows[] = [
        $lastName,
        $firstName,
        $middleName,
        $extension,
        $birthdate,
        $assistance['subtype'],
        $assistance['detail'],
        $frequencySubject,
        $caseKey,
        $referenceNo,
        $remarks,
    ];
}

for ($i = 1; $i <= $referenceCount; $i++) {
    if ($i <= $exactDuplicateCount) {
        $base = $uploadProfiles[$i - 1];
        $referenceRows[] = [
            $base['last_name'],
            $base['first_name'],
            $base['middle_name'],
            $base['extension_name'],
            $base['birthdate'],
            'REF-EXACT-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'Intentional exact duplicate for upload reference '.$base['reference_no'],
        ];
        continue;
    }

    if ($i <= $exactDuplicateCount + $fuzzyDuplicateCount) {
        $base = $uploadProfiles[$i - $exactDuplicateCount - 1];
        $mutatedLastName = str_ends_with($base['last_name'], 's')
            ? rtrim($base['last_name'], 's')
            : $base['last_name'].'s';
        $mutatedFirstName = strlen($base['first_name']) > 3
            ? substr($base['first_name'], 0, -1)
            : $base['first_name'].'a';
        $mutatedBirthdate = date('Y-m-d', strtotime($base['birthdate'].' +1 day'));

        $referenceRows[] = [
            $mutatedLastName,
            $mutatedFirstName,
            $base['middle_name'],
            $base['extension_name'],
            $mutatedBirthdate,
            'REF-FUZZY-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'Intentional fuzzy-match sample for upload reference '.$base['reference_no'],
        ];
        continue;
    }

    $lastName = $lastNames[array_rand($lastNames)];
    $firstName = $firstNames[array_rand($firstNames)];
    $middleName = $middleNames[array_rand($middleNames)];
    $extension = $extensions[array_rand($extensions)];
    $birthdate = sprintf(
        '%04d-%02d-%02d',
        mt_rand(1968, 2006),
        mt_rand(1, 12),
        mt_rand(1, 28)
    );

    $referenceRows[] = [
        $lastName,
        $firstName,
        $middleName,
        $extension,
        $birthdate,
        'REF-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        $remarksPool[array_rand($remarksPool)],
    ];
}

$datasets = [
    'main_upload_'.$uploadCount => $uploadRows,
    'reference_list_'.$referenceCount => $referenceRows,
];

foreach ($datasets as $name => $rows) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1), $value);
        }
    }

    foreach (range('A', $sheet->getHighestColumn()) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $xlsxWriter = new Xlsx($spreadsheet);
    $xlsxWriter->save($outputDir.'/deduplication_'.$name.'_sample.xlsx');

    $csvWriter = new Csv($spreadsheet);
    $csvWriter->save($outputDir.'/deduplication_'.$name.'_sample.csv');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
}

echo "Sample files generated in {$outputDir}".PHP_EOL;
echo "- Upload rows: {$uploadCount}".PHP_EOL;
echo "- Reference rows: {$referenceCount}".PHP_EOL;
echo "- Exact duplicate candidates: {$exactDuplicateCount}".PHP_EOL;
echo "- Fuzzy finding candidates: {$fuzzyDuplicateCount}".PHP_EOL;
