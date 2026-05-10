<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBulkDeduplicationRun;
use App\Models\BulkDeduplicationRun;
use App\Services\AuditLogService;
use App\Services\BulkDeduplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkDeduplicationController extends Controller
{
    public function __construct(
        protected BulkDeduplicationService $deduplication,
        protected AuditLogService $auditLogs
    ) {
    }

    public function index(Request $request): View
    {
        $runs = $this->visibleRunsQuery($request)
            ->latest()
            ->take(10)
            ->get();

        $selectedRun = $request->filled('run')
            ? $this->visibleRunsQuery($request)->find($request->integer('run'))
            : $runs->first();

        $role = $request->user()?->role;
        $isReportingOfficer = $role === 'reporting_officer';

        return view('deduplication.index', [
            'runs' => $runs,
            'selectedRun' => $selectedRun,
            'uploadRoute' => $isReportingOfficer ? route('reporting.deduplication.store') : route('admin.deduplication.store'),
            'indexRoute' => $isReportingOfficer ? route('reporting.deduplication.index') : route('admin.deduplication.index'),
            'dashboardRoute' => $isReportingOfficer ? route('reporting.dashboard') : route('admin.dashboard'),
            'downloadBaseRoute' => $isReportingOfficer ? 'reporting.deduplication.download' : 'admin.deduplication.download',
            'statusBaseRoute' => $isReportingOfficer
                ? route('reporting.deduplication.status', ['run' => '__RUN__'])
                : route('admin.deduplication.status', ['run' => '__RUN__']),
            'selectedRunStatus' => $selectedRun?->status,
            'isReportingOfficer' => $isReportingOfficer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spreadsheet' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'compare_source' => ['required', 'in:system,uploaded_list'],
            'reference_spreadsheet' => ['nullable', 'file', 'mimes:xlsx,xls,csv'],
            'apply_frequency_rules' => ['nullable', 'boolean'],
        ]);

        if (($validated['compare_source'] ?? 'system') === 'uploaded_list' && ! $request->hasFile('reference_spreadsheet')) {
            return back()
                ->withErrors(['reference_spreadsheet' => 'Upload the comparison list when using the uploaded list option.'])
                ->withInput();
        }

        $run = $this->deduplication->queueUpload(
            $validated['spreadsheet'],
            $request->user(),
            [
                'compare_source' => $validated['compare_source'],
                'apply_frequency_rules' => $request->boolean('apply_frequency_rules'),
            ],
            $request->file('reference_spreadsheet')
        );

        ProcessBulkDeduplicationRun::dispatch($run->id);

        $this->auditLogs->log($request, 'bulk_deduplication.created', $run, [
            'original_filename' => $run->original_filename,
            'summary' => $run->summary,
            'status' => $run->status,
        ]);

        return redirect()
            ->to(route($request->user()->role === 'reporting_officer'
                ? 'reporting.deduplication.index'
                : 'admin.deduplication.index', ['run' => $run->id]))
            ->with('success', 'Bulk deduplication was queued. You can leave this page and come back while the processor continues in the background.');
    }

    public function status(Request $request, BulkDeduplicationRun $run): JsonResponse
    {
        abort_unless($this->visibleRunsQuery($request)->whereKey($run->id)->exists(), 403);

        $run->refresh();

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'progress_percentage' => (int) $run->progress_percentage,
            'progress_message' => $run->progress_message,
            'error_message' => $run->error_message,
            'summary' => $run->summary ?? [],
            'completed_at' => $run->completed_at?->toDateTimeString(),
            'failed_at' => $run->failed_at?->toDateTimeString(),
        ]);
    }

    public function download(Request $request, BulkDeduplicationRun $run, string $type): StreamedResponse
    {
        abort_unless($this->visibleRunsQuery($request)->whereKey($run->id)->exists(), 403);
        abort_unless(in_array($type, ['clean', 'duplicates', 'findings'], true), 404);
        abort_unless($run->status === 'completed', 404);

        $rows = $this->deduplication->exportRows($run, $type);
        $filename = 'bulk-deduplication-'.$type.'-'.$run->id.'.xlsx';

        $this->auditLogs->log($request, 'bulk_deduplication.downloaded', $run, [
            'download_type' => $type,
        ]);

        return response()->streamDownload(function () use ($rows, $type) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(Str::title(str_replace('_', ' ', $type)));

            $flattenedRows = $this->flattenRowsForExport($rows, $type);
            $headers = array_keys($flattenedRows[0] ?? ['message' => 'No rows available']);

            foreach ($headers as $index => $header) {
                $sheet->setCellValueByColumnAndRow($index + 1, 1, Str::headline($header));
            }

            foreach ($flattenedRows as $rowIndex => $row) {
                foreach (array_values($row) as $columnIndex => $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, (string) $value);
                }
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function flattenRowsForExport(array $rows, string $type): array
    {
        if ($rows === []) {
            return [['message' => 'No rows available']];
        }

        if ($type !== 'findings') {
            return $rows;
        }

        return array_map(function (array $row) {
            $matches = collect($row['matches'] ?? [])
                ->map(fn (array $match) => ($match['matched_name'] ?? 'Unknown').' ['.($match['source'] ?? '').'] '.($match['matched_birthdate'] ?? '').' '.$match['similarity_score'])
                ->implode(' | ');

            unset($row['matches']);
            $row['possible_matches'] = $matches;

            return $row;
        }, $rows);
    }

    protected function visibleRunsQuery(Request $request)
    {
        $query = BulkDeduplicationRun::query();

        if ($request->user()?->role === 'reporting_officer') {
            $query->where('user_id', $request->user()->id);
        }

        return $query;
    }
}
