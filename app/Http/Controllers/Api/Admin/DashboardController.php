<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $totalItems = Item::count();
        $pendingOrders = Order::where('status', 'new')->count();

        if ($user->isAgent()) {
            $totalOrders = Order::where('processed_by', $user->id)->count();
            $totalRevenue = Payment::where('status', 'success')
                ->whereHas('order', fn ($q) => $q->where('processed_by', $user->id))
                ->sum('amount');
            $recentOrders = Order::with('items', 'processor')
                ->where(fn ($q) => $q->where('processed_by', $user->id)->orWhere('status', 'new'))
                ->latest()
                ->limit(10)
                ->get();
        } else {
            $totalOrders = Order::count();
            $totalRevenue = Payment::where('status', 'success')->sum('amount');
            $recentOrders = Order::with('items', 'processor')->latest()->limit(10)->get();
        }

        $agentStatus = PosSyncAgentStatus::where('source', 'pos-agent')->first();
        $lastBatch = PosSyncBatch::latest()->first();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_items' => $totalItems,
                'total_orders' => $totalOrders,
                'total_revenue' => round((float) $totalRevenue, 2),
                'pending_orders' => $pendingOrders,
            ],
            'recent_orders' => $recentOrders->map(fn (Order $order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'processed_by' => $order->processor?->name,
                'items_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at?->toISOString(),
            ])->values(),
            'pos_sync' => [
                'agent_online' => $agentStatus && $agentStatus->last_seen_at && $agentStatus->last_seen_at->gte(now()->subMinutes(2)),
                'last_seen_at' => $agentStatus?->last_seen_at?->toIso8601String(),
                'version' => $agentStatus?->version,
                'last_batch' => $lastBatch ? [
                    'id' => $lastBatch->id,
                    'status' => $lastBatch->status,
                    'total_items' => $lastBatch->total_items,
                    'processed_items' => $lastBatch->processed_items,
                    'failed_items' => $lastBatch->failed_items,
                    'created_at' => $lastBatch->created_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }
}
