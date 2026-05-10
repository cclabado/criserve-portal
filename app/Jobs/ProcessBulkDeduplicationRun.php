<?php

namespace App\Jobs;

use App\Models\BulkDeduplicationRun;
use App\Notifications\BulkDeduplicationFinishedNotification;
use App\Services\BulkDeduplicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBulkDeduplicationRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $runId
    ) {
        $this->onQueue('deduplication');
    }

    public function handle(BulkDeduplicationService $deduplication): void
    {
        $run = BulkDeduplicationRun::query()->find($this->runId);

        if (! $run) {
            return;
        }

        $run = $deduplication->processRun($run);

        if ($run->user) {
            $run->user->notify(new BulkDeduplicationFinishedNotification($run));
        }
    }

    public function failed(Throwable $exception): void
    {
        $run = BulkDeduplicationRun::query()->find($this->runId);

        if (! $run) {
            return;
        }

        $run->forceFill([
            'status' => 'failed',
            'progress_message' => 'Deduplication failed.',
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
        ])->save();

        if ($run->user) {
            $run->user->notify(new BulkDeduplicationFinishedNotification($run));
        }
    }
}
