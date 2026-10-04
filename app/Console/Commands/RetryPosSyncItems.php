<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPosSyncBatch;
use App\Models\PosSyncBatch;
use Illuminate\Console\Command;

class RetryPosSyncItems extends Command
{
    protected $signature = 'pos:sync:retry
        {--batch= : Only retry failed items from this batch id}
        {--limit=500 : Maximum failed items to retry}';

    protected $description = 'Re-queue failed POS sync items for processing';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $query = \App\Models\PosSyncItem::query()->where('status', 'failed');

        if ($this->option('batch')) {
            $query->where('batch_id', (int) $this->option('batch'));
        }

        $failedItems = $query->orderBy('id')->limit($limit)->get();

        if ($failedItems->isEmpty()) {
            $this->info('No failed POS sync items to retry.');

            return Command::SUCCESS;
        }

        $batchIds = $failedItems->pluck('batch_id')->unique();

        $failedItems->each(function ($item) {
            $item->update([
                'status' => 'pending',
                'error_message' => null,
                'processed_at' => null,
            ]);
        });

        foreach ($batchIds as $batchId) {
            PosSyncBatch::whereKey($batchId)->update([
                'status' => 'pending',
                'failed_items' => 0,
            ]);
            ProcessPosSyncBatch::dispatch($batchId)->onQueue('pos-sync');
        }

        $this->info("Retrying {$failedItems->count()} item(s) across {$batchIds->count()} batch(es).");

        return Command::SUCCESS;
    }
}
