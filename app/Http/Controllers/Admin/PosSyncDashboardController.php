<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPosSyncBatch;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
use App\Models\PosSyncItem;
use App\Models\User;
use Illuminate\Http\Request;

class PosSyncDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403, 'You do not have permission to view POS sync.');
        }

        $batches = PosSyncBatch::withCount([
            'items',
            'items as processed_count' => fn ($q) => $q->where('status', 'processed'),
            'items as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ])->when($request->filled('search'), function ($q) use ($request) {
            $search = trim($request->input('search'));
            $q->where(function ($b) use ($search) {
                $b->where('sync_id', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('error_message', 'like', "%{$search}%");
                if (preg_match('/[0-9]/', $search)) {
                    $b->orWhere('id', (int) $search);
                }
            });
        })->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => PosSyncBatch::count(),
            'completed' => PosSyncBatch::where('status', 'completed')->count(),
            'failed' => PosSyncBatch::where('status', 'failed')->count(),
            'processing' => PosSyncBatch::whereIn('status', ['pending', 'processing'])->count(),
            'items_processed_24h' => PosSyncItem::where('status', 'processed')
                ->where('processed_at', '>=', now()->subHours(24))
                ->count(),
            'last_batch' => PosSyncBatch::latest()->first(),
        ];

        $agentStatus = PosSyncAgentStatus::where('source', 'pos-agent')->first();
        $agentUser = User::where('email', 'pos-sync@mdelight.local')->first();

        $tokens = $agentUser
            ? $agentUser->tokens()->where('name', 'pos-sync')->orderByDesc('created_at')->get()
            : collect();

        return view('admin.pos-sync.index', compact(
            'batches',
            'stats',
            'agentStatus',
            'agentUser',
            'tokens'
        ));
    }

    public function show(Request $request, PosSyncBatch $batch)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403, 'You do not have permission to view POS sync.');
        }

        $items = $batch->items()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.pos-sync.show', compact('batch', 'items'));
    }

    /**
     * Lightweight agent/connection status consumed by the dashboard widget and
     * any admin page that wants to show whether the POS agent is online.
     * Accessible to all admin-area users so everyone can spot outages.
     */
    public function status()
    {
        if (! auth()->user()->isAdminOrAgent()) {
            abort(403);
        }

        $agentStatus = PosSyncAgentStatus::where('source', 'pos-agent')->first();
        $lastBatch = PosSyncBatch::latest()->first();
        $failedBatches24h = PosSyncBatch::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return response()->json([
            'agent_online' => $agentStatus && $agentStatus->last_seen_at && $agentStatus->last_seen_at->gte(now()->subMinutes(2)),
            'last_seen_at' => $agentStatus?->last_seen_at?->toIso8601String(),
            'version' => $agentStatus?->version,
            'last_batch' => $lastBatch ? [
                'id' => $lastBatch->id,
                'status' => $lastBatch->status,
                'sync_id' => $lastBatch->sync_id,
                'total_items' => $lastBatch->total_items,
                'processed_items' => $lastBatch->processed_items,
                'failed_items' => $lastBatch->failed_items,
                'created_at' => $lastBatch->created_at?->toIso8601String(),
            ] : null,
            'failed_batches_24h' => $failedBatches24h,
        ]);
    }

    /**
     * JSON list of a batch's items including the fields that were changed,
     * used by the expandable diff viewer on the POS Sync index page.
     */
    public function items(Request $request, PosSyncBatch $batch)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $limit = min(max((int) $request->input('limit', 300), 1), 1000);

        $items = $batch->items()
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (PosSyncItem $i) => [
                'pos_item_id' => $i->pos_item_id,
                'action' => $i->action,
                'status' => $i->status,
                'error_message' => $i->error_message,
                'changed_fields' => $this->changedFields($i),
            ]);

        return response()->json([
            'items' => $items,
            'shown' => $items->count(),
            'total' => $batch->total_items,
        ]);
    }

    protected function changedFields(PosSyncItem $item): array
    {
        $payload = is_array($item->payload) ? $item->payload : [];

        if (($item->action ?? '') === 'deactivate') {
            return [['field' => 'status', 'value' => 'deactivated']];
        }

        $skip = ['pos_item_id', 'action', 'barcodes'];
        $fields = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                continue;
            }
            $fields[] = ['field' => $key, 'value' => (string) $value];
        }

        return $fields;
    }

    public function createToken(Request $request)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $user = User::where('email', 'pos-sync@mdelight.local')->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'POS sync agent user not found.'], 404);
        }

        $token = $user->createToken('pos-sync', ['pos:sync']);

        return response()->json([
            'success' => true,
            'message' => 'New API token created. Copy it now — it is shown only once.',
            'plain_text_token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
        ]);
    }

    public function revokeToken(Request $request, $tokenId)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $user = User::where('email', 'pos-sync@mdelight.local')->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'POS sync agent user not found.'], 404);
        }

        $token = $user->tokens()->where('id', $tokenId)->first();
        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Token not found.'], 404);
        }

        $token->delete();

        return response()->json(['success' => true, 'message' => 'Token revoked. It can no longer be used.']);
    }

    public function retry(Request $request, PosSyncBatch $batch)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $failed = $batch->items()->where('status', 'failed')->get();
        if ($failed->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No failed items to retry.']);
        }

        $batch->items()->where('status', 'failed')->update([
            'status' => 'pending',
            'error_message' => null,
            'processed_at' => null,
        ]);
        $batch->update(['status' => 'pending', 'failed_items' => 0]);

        ProcessPosSyncBatch::dispatch($batch->id)->onQueue('pos-sync');

        return response()->json([
            'success' => true,
            'message' => 'Re-queued ' . $failed->count() . ' failed item(s).',
        ]);
    }

    public function failedItems(Request $request, PosSyncBatch $batch)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $items = $batch->items()->where('status', 'failed')->get()->map(fn ($i) => [
            'pos_item_id' => $i->pos_item_id,
            'action' => $i->action,
            'error_message' => $i->error_message,
        ]);

        return response()->json(['items' => $items]);
    }
}
