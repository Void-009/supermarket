<?php

namespace App\Http\Middleware;

use App\Services\SyncCleanupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Poor-man's cron: runs the POS sync data cleanup from web traffic so hosts
 * without shell/cron access still get automatic maintenance. The storefront
 * requests and the sync agent's 60-second heartbeat both pass through here.
 */
class SyncCleanup
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->maybeRunCleanup();

        return $next($request);
    }

    protected function maybeRunCleanup(): void
    {
        try {
            $service = app(SyncCleanupService::class);
            if ($service->isDue()) {
                $service->run();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
