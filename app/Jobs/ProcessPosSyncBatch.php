<?php

namespace App\Jobs;

use App\Models\PosSyncBatch;
use App\Services\PosSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPosSyncBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $batchId)
    {
    }

    public function handle(PosSyncService $service): void
    {
        $batch = PosSyncBatch::findOrFail($this->batchId);

        if ($batch->status === 'duplicate' || $batch->status === 'completed') {
            return;
        }

        $batch->update(['status' => 'processing']);

        $items = $batch->items()->where('status', 'pending')->get();

        $processed = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $outcome = DB::transaction(function () use ($service, $item): array {
                    return $service->process($item->payload);
                });

                if (($outcome['result'] ?? 'failed') === 'failed') {
                    $failed++;
                    $item->update([
                        'status' => 'failed',
                        'error_message' => $outcome['error'] ?? 'Unknown error',
                        'processed_at' => now(),
                    ]);
                } else {
                    $processed++;
                    $item->update([
                        'status' => 'processed',
                        'error_message' => $outcome['reason'] ?? null,
                        'processed_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                $item->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ]);
            }

            $batch->updateQuietly([
                'processed_items' => $processed + $failed,
                'failed_items' => $failed,
            ]);
        }

        try {
            $service->syncCategories();
        } catch (\Throwable $e) {
            // Category maintenance must not fail the item batch itself.
        }

        $batch->update([
            'status' => $processed === 0 && $failed > 0 ? 'failed' : 'completed',
            'processed_items' => $processed + $failed,
            'failed_items' => $failed,
        ]);
    }
}
