<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosSyncRequest;
use App\Jobs\ProcessPosSyncBatch;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
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
