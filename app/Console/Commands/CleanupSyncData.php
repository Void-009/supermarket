<?php

namespace App\Console\Commands;

use App\Services\SyncCleanupService;
use Illuminate\Console\Command;

class CleanupSyncData extends Command
{
    protected $signature = 'sync:cleanup {--days=30 : purge records/files older than this many days}';

    protected $description = 'Purge old POS sync history, stale sync logs and rotated framework log files';

    public function handle(SyncCleanupService $service): int
    {
        $days = max(1, (int) $this->option('days'));

        $this->info("Purging records/files older than {$days} day(s)...");

        $result = $service->run($days);

        $this->info('- pos_sync_batches (with their items): '.$result['batches_purged'].' purged');
        $this->info('- sync_logs (old manual sync): '.$result['sync_logs_purged'].' purged');
        $this->info('- rotated framework log files: '.$result['log_files_removed'].' removed');

        return Command::SUCCESS;
    }
}
