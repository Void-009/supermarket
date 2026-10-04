<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('view_revenue'), 403, 'You do not have permission to view revenue.');

        $user = auth()->user();
        $range = $request->input('range', 'all_time');
        $from = null;
        $to = null;

        $now = Carbon::now();

        if ($range === 'today') {
            $from = $now->copy()->startOfDay();
            $to = $now->copy()->endOfDay();
        } elseif ($range === 'yesterday') {
            $from = $now->copy()->subDay()->startOfDay();
            $to = $now->copy()->subDay()->endOfDay();
        } elseif ($range === 'last_7_days') {
            $from = $now->copy()->subDays(7)->startOfDay();
            $to = $now->copy();
        } elseif ($range === 'last_14_days') {
            $from = $now->copy()->subDays(14)->startOfDay();
            $to = $now->copy();
        } elseif ($range === 'last_30_days') {
            $from = $now->copy()->subDays(30)->startOfDay();
            $to = $now->copy();
        } elseif ($range === 'last_90_days') {
            $from = $now->copy()->subDays(90)->startOfDay();
            $to = $now->copy();
        } elseif ($range === 'this_month') {
            $from = $now->copy()->startOfMonth();
            $to = $now->copy();
        } elseif ($range === 'last_month') {
            $from = $now->copy()->subMonth()->startOfMonth();
            $to = $now->copy()->subMonth()->endOfMonth();
        } elseif ($range === 'this_year') {
            $from = $now->copy()->startOfYear();
            $to = $now->copy();
        } elseif ($range === 'last_year') {
            $from = $now->copy()->subYear()->startOfYear();
            $to = $now->copy()->subYear()->endOfYear();
        } elseif ($range === 'custom') {
            $from = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->startOfDay() : null;
            $to = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->endOfDay() : null;
        }

        $orderQuery = function ($query) use ($from, $to) {
            if ($from) {
                $query->where('orders.created_at', '>=', $from);
            }
            if ($to) {
                $query->where('orders.created_at', '<=', $to);
            }
        };

        $breakdown = $this->breakdown($user->isAgent() ? $user->id : null, $from, $to);
        $totalRevenue = array_sum($breakdown);

        $totalOrders = Order::whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->when($user->isAgent(), fn ($q) => $q->where('processed_by', $user->id))
            ->when($from || $to, $orderQuery)
            ->count();

        if ($user->isAgent()) {
            $agentRevenue = collect([[
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'revenue' => round((float) $totalRevenue, 2),
                'orders_count' => $totalOrders,
                'percentage' => $totalRevenue > 0 ? 100 : 0,
            ]])->values();
        } else {
            $agentRevenue = User::whereIn('role', ['admin', 'agent'])
            ->select('id', 'name', 'role')
            ->withCount(['processedOrders' => function ($q) use ($orderQuery) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'success'));
                $orderQuery($q);
            }])
            ->withSum(['processedOrders' => function ($q) use ($orderQuery) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'success'));
                $orderQuery($q);
            }], 'total_amount')
            ->orderByDesc('processed_orders_sum_total_amount')
            ->get()
            ->map(function ($u) use ($totalRevenue) {
                $revenue = $u->processed_orders_sum_total_amount ?? 0;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->role,
                    'revenue' => round((float) $revenue, 2),
                    'orders_count' => $u->processed_orders_count ?? 0,
                    'percentage' => $revenue > 0 ? round(($revenue / max($totalRevenue, 1)) * 100, 1) : 0,
                ];
            })
            ->values();
        }

        return response()->json([
            'success' => true,
            'range' => $range,
            'from' => $from?->toISOString(),
            'to' => $to?->toISOString(),
            'total_revenue' => round((float) $totalRevenue, 2),
            'total_orders' => $totalOrders,
            'breakdown' => [
                'pickup' => round((float) $breakdown['pickup'], 2),
                'bank_transfer' => round((float) $breakdown['bank_transfer'], 2),
                'delivery' => round((float) $breakdown['delivery'], 2),
            ],
            'agent_revenue' => $agentRevenue,
        ]);
    }

    private function breakdown(?int $processedBy, ?Carbon $from, ?Carbon $to): array
    {
        $sum = function (string $paymentMethod, ?string $deliveryMethod = null) use ($processedBy, $from, $to) {
            return (float) Payment::where('status', 'success')
                ->whereHas('order', function ($q) use ($processedBy, $from, $to, $paymentMethod, $deliveryMethod) {
                    if ($processedBy) {
                        $q->where('orders.processed_by', $processedBy);
                    }
                    if ($from) {
                        $q->where('orders.created_at', '>=', $from);
                    }
                    if ($to) {
                        $q->where('orders.created_at', '<=', $to);
                    }
                    $q->where('orders.payment_method', $paymentMethod);
                    if ($deliveryMethod) {
                        $q->where('orders.delivery_method', $deliveryMethod);
                    }
                })
                ->sum('amount');
        };

        return [
            'pickup' => $sum('cash_on_delivery', 'pickup'),
            'bank_transfer' => $sum('bank_transfer'),
            'delivery' => $sum('cash_on_delivery', 'delivery'),
        ];
    }
}
