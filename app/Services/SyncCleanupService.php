<?php

namespace App\Services;

use App\Models\PosSyncBatch;
use App\Models\Setting;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class SyncCleanupService
{
    public const LAST_RUN_KEY = 'sync_cleanup_last_run';

    public const LAST_RESULT_KEY = 'sync_cleanup_last_result';

    public function run(int $days = 30): array
    {
        // Reserve the marker first so a failing run retries only after the
        // interval, not on every single web request.
        $runAt = now()->toDateTimeString();
        Setting::set(self::LAST_RUN_KEY, $runAt, 'sync');

        $cutoff = Carbon::now()->subDays($days);

        $batchCount = PosSyncBatch::where('created_at', '<', $cutoff)
            ->whereIn('status', ['completed', 'failed', 'duplicate'])
            ->delete();

        $syncLogCount = SyncLog::where('created_at', '<', $cutoff)->delete();

        $logFilesRemoved = $this->purgeOldLogFiles($cutoff);

        $this->clearRegenerableFiles();

        $result = [
            'run_at' => $runAt,
            'cutoff' => $cutoff->toDateTimeString(),
            'batches_purged' => $batchCount,
            'sync_logs_purged' => $syncLogCount,
            'log_files_removed' => $logFilesRemoved,
        ];

        Setting::set(self::LAST_RESULT_KEY, json_encode($result), 'sync');

        return $result;
    }

    public function isDue(int $intervalHours = 24): bool
    {
        $lastRun = Setting::get(self::LAST_RUN_KEY);
        if (! $lastRun) {
            return true;
        }
        try {
            $last = Carbon::parse($lastRun);
        } catch (\Throwable) {
            return true;
        }

        return $last->addHours($intervalHours)->isPast();
    }

    private function purgeOldLogFiles(Carbon $cutoff): int
    {
        $logDir = storage_path('logs');
        if (! is_dir($logDir)) {
            return 0;
        }

        $active = 'laravel-'.now()->format('Y-m-d').'.log';
        $removed = 0;

        foreach (File::files($logDir) as $file) {
            $name = $file->getFilename();
            if ($name === $active || $name === '.gitignore') {
                continue;
            }
            if ($file->getMTime() >= $cutoff->getTimestamp()) {
                continue;
            }
            File::delete($file->getPathname());
            $removed++;
        }

        return $removed;
    }

    private function clearRegenerableFiles(): void
    {
        // Compiled Blade views - rebuilt automatically on the next render.
        $views = storage_path('framework/views');
        if (is_dir($views)) {
            foreach (File::files($views) as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getPathname());
                }
            }
        }

        // Compiled config/route/event/package caches - rebuilt automatically.
        foreach (['config.php', 'routes-v7.php', 'routes.php', 'events.php', 'packages.php', 'services.php'] as $cached) {
            $path = base_path('bootstrap/cache/'.$cached);
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
