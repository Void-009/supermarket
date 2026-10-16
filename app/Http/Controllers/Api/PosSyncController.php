<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosSyncReconcileRequest;
use App\Http\Requests\PosSyncRequest;
use App\Jobs\ProcessPosSyncBatch;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
use App\Services\PosSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PosSyncController extends Controller
{
    /**
     * Health check used by the agent's "Test connection" button and heartbeat.
     * Requires a valid Sanctum token scoped to pos:sync. Also records the last
     * time the agent was seen so the admin page can show ONLINE even when idle.
     */
    public function ping(Request $request): JsonResponse
    {
        $this->heartbeat($request->query('source', 'pos-agent'), $request->query('version'));

        return response()->json([
            'message' => 'pong',
            'time' => now()->toIso8601String(),
            'agent' => $request->user()?->name,
        ]);
    }

    public function store(PosSyncRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->heartbeat($data['source'] ?? 'pos-agent', $request->header('X-Agent-Version'));

        // Idempotency: a repeated sync_id is acknowledged but never reprocessed.
        $existing = PosSyncBatch::where('sync_id', $data['sync_id'])->first();
        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'batch_id' => $existing->id,
                'message' => 'This sync_id has already been received.',
            ]);
        }

        $items = $data['items'];

        foreach ($items as $item) {
            if (($item['action'] ?? 'update') === 'create'
                && trim((string) ($item['name'] ?? '')) === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => "Item [{$item['pos_item_id']}]: name is required when action is create.",
                ], 422);
            }
        }

        $user = $request->user();

        $batch = PosSyncBatch::create([
            'sync_id' => $data['sync_id'],
            'source' => $data['source'] ?? 'pos-agent',
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'status' => 'pending',
            'total_items' => count($items),
            'processed_items' => 0,
            'failed_items' => 0,
            'processed_queue' => 'pos-sync',
        ]);

        $batch->items()->createMany(
            array_map(fn (array $item) => [
                'pos_item_id' => $item['pos_item_id'],
                'action' => $item['action'],
                'payload' => $item,
                'status' => 'pending',
            ], $items)
        );

        ProcessPosSyncBatch::dispatch($batch->id)->onQueue('pos-sync');

        return response()->json([
            'status' => 'accepted',
            'batch_id' => $batch->id,
            'sync_id' => $batch->sync_id,
            'received_items' => count($items),
        ], 202);
    }

    /**
     * Reports how a batch the agent sent is doing on the queue. The agent
     * only marks its local changes as uploaded once this returns a terminal
     * status, so a batch that never ran (worker down) or was purged before
     * processing keeps the changes queued for resend instead of being lost.
     */
    public function batchStatus(string $syncId): JsonResponse
    {
        $batch = PosSyncBatch::where('sync_id', $syncId)->first();

        if (! $batch) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Unknown sync_id (the batch may have been purged before it was processed).',
            ], 404);
        }

        return response()->json([
            'status' => $batch->status,
            'total_items' => $batch->total_items,
            'processed_items' => $batch->processed_items,
            'failed_items' => $batch->failed_items,
            'failed_items_detail' => $batch->items()
                ->where('status', 'failed')
                ->pluck('error_message', 'pos_item_id'),
            'created_at' => $batch->created_at?->toIso8601String(),
            'updated_at' => $batch->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Receives the agent's complete POS Item Id inventory and tombstones
     * synced variations that are no longer in it. Tombstoned rows are
     * hidden (never deleted) and can be revived by a fingerprint match
     * when the POS re-creates the variation under a fresh id.
     */
    public function reconcile(PosSyncReconcileRequest $request, PosSyncService $service): JsonResponse
    {
        $data = $request->validated();

        $this->heartbeat($data['source'] ?? 'pos-agent', $request->header('X-Agent-Version'));

        $result = $service->reconcileCatalog(
            $data['pos_item_ids'],
            $data['inactive_pos_item_ids'] ?? [],
            now(),
            (bool) ($data['dry_run'] ?? false)
        );

        return response()->json([
            'status' => $result['skipped'] ? 'skipped' : 'ok',
            'dry_run' => (bool) ($data['dry_run'] ?? false),
            'reason' => $result['reason'],
            'seen_count' => $result['seen_count'],
            'reference_count' => $result['reference_count'],
            'missing_products' => $result['missing_products'],
            'missing_variations' => $result['missing_variations'],
            'inactive_hidden' => $result['inactive_hidden'],
            'tombstoned' => $result['tombstoned'],
            'tombstoned_ids' => $result['ids'],
        ]);
    }

    /**
     * Issues a fresh pos:sync token and revokes the one used to authenticate.
     * Lets the POS agent renew its own credential before the 30-day expiry, so
     * staff never have to edit config.json by hand. Only a currently-valid
     * pos:sync token can call this (route middleware), and the new token is
     * scoped to the same ability.
     */
    public function rotateToken(Request $request): JsonResponse
    {
        $user = $request->user();

        $expiration = (int) config('sanctum.expiration');
        $expiresAt = $expiration > 0 ? now()->addMinutes($expiration) : null;
        $token = $user->createToken('pos-sync', ['pos:sync'], $expiresAt);

        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        return response()->json([
            'success' => true,
            'plain_text_token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ]);
    }

    protected function heartbeat(string $source, ?string $version): void
    {
        PosSyncAgentStatus::updateOrCreate(
            ['source' => trim($source) ?: 'pos-agent'],
            [
                'last_seen_at' => now(),
                'version' => $version,
            ]
        );
    }
}
