<?php

namespace App\Jobs;

use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunPosSyncArtisan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $logId,
        public string $mode = 'all',
    ) {
    }

    public function handle(): void
    {
        $log = SyncLog::find($this->logId);
        if (! $log || $log->status !== 'in_progress') {
            return;
        }

        try {
            Artisan::call('pos:sync', [
                '--mode' => $this->mode,
                '--log-id' => $this->logId,
            ]);
        } catch (\Throwable $e) {
            Log::error("pos:sync artisan failed: {$e->getMessage()}", [
                'log_id' => $this->logId,
                'mode' => $this->mode,
            ]);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $log = SyncLog::find($this->logId);
        if ($log && $log->status === 'in_progress') {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Queue job failed: '.$exception->getMessage(),
            ]);
        }
    }
}
