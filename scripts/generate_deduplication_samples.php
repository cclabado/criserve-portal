<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$outputDir = __DIR__.'/../storage/app/deduplication-samples';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$datasets = [
    'main_upload' => [
        ['last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate', 'assistance_subtype', 'assistance_detail', 'frequency_subject', 'frequency_case_key', 'reference_no', 'remarks'],
        ['Labado', 'Carl', 'Competente', '', '1990-01-01', 'Medical Assistance', 'Hospital Bill', 'client', 'case-1001', 'TEST-001', 'Likely duplicate against system if matching record exists'],
        ['Santos', 'Maria', 'Cruz', '', '1992-05-12', 'Transportation Assistance', '', 'client', 'trip-2001', 'TEST-002', 'Should stay clean unless matched in selected comparison source'],
        ['Dela Cruz', 'Juan', 'Reyes', 'Jr', '1988-11-03', 'Burial Assistance', '', 'beneficiary', 'burial-3001', 'TEST-003', 'Good row for clean output'],
        ['Gonzales', 'Ana', 'Lopez', '', '1995-07-21', 'Medical Assistance', 'Laboratory', 'client', 'case-1002', 'TEST-004', 'Used for fuzzy finding against reference list'],
        ['Garcia', 'Pedro', '', '', '1980-02-29', '', '', '', '', 'TEST-005', 'No assistance fields so frequency can be skipped'],
    ],
    'reference_list' => [
        ['last_name', 'first_name', 'middle_name', 'extension_name', 'birthdate', 'reference_no', 'remarks'],
        ['Santos', 'Maria', 'Cruz', '', '1992-05-12', 'REF-001', 'Exact duplicate for TEST-002'],
        ['Dela Cruz', 'Juna', 'Reyes', 'Jr', '1988-03-11', 'REF-002', 'Fuzzy finding for TEST-003 due to first-name typo and reversed month/day'],
        ['Gonzalez', 'Ana', 'Lopez', '', '1995-07-22', 'REF-003', 'Fuzzy finding for TEST-004 due to surname/date near-match'],
        ['Rivera', 'Luis', 'Martinez', '', '1991-09-10', 'REF-004', 'Non-match control row'],
    ],
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
