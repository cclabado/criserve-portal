<?php

namespace App\Jobs;

use App\Models\PayoutBatch;
use App\Services\PayoutImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessPayoutBatchImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $batchId
    ) {
        $this->onQueue('imports');
    }

    public function handle(PayoutImportService $imports): void
    {
        $batch = PayoutBatch::query()->find($this->batchId);

        if (! $batch) {
            return;
        }

        $imports->processBatch($batch);
    }

    public function failed(Throwable $exception): void
    {
        $batch = PayoutBatch::query()->find($this->batchId);

        if (! $batch) {
            return;
        }

        app(PayoutImportService::class)->markBatchFailed($batch, $exception->getMessage());
    }
}
