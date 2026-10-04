<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
use Illuminate\Http\JsonResponse;

class PosSyncController extends Controller
{
    public function status(): JsonResponse
    {
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
}
