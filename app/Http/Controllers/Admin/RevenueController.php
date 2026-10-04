<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $range = $request->input('range', 'all_time');
        $from = null;
        $to = null;

        $now = Carbon::now();

        match ($range) {
            'today' => [
                $from = $now->copy()->startOfDay(),
                $to = $now->copy()->endOfDay(),
            ],
            'yesterday' => [
                $from = $now->copy()->subDay()->startOfDay(),
                $to = $now->copy()->subDay()->endOfDay(),
            ],
            'last_7_days' => [
                $from = $now->copy()->subDays(7)->startOfDay(),
                $to = $now->copy(),
            ],
            'last_14_days' => [
                $from = $now->copy()->subDays(14)->startOfDay(),
                $to = $now->copy(),
            ],
            'last_30_days' => [
                $from = $now->copy()->subDays(30)->startOfDay(),
                $to = $now->copy(),
            ],
            'last_90_days' => [
                $from = $now->copy()->subDays(90)->startOfDay(),
                $to = $now->copy(),
            ],
            'this_month' => [
                $from = $now->copy()->startOfMonth(),
                $to = $now->copy(),
            ],
            'last_month' => [
                $from = $now->copy()->subMonth()->startOfMonth(),
                $to = $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_year' => [
                $from = $now->copy()->startOfYear(),
                $to = $now->copy(),
            ],
            'last_year' => [
                $from = $now->copy()->subYear()->startOfYear(),
                $to = $now->copy()->subYear()->endOfYear(),
            ],
            'custom' => [
                $from = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->startOfDay() : null,
                $to = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->endOfDay() : null,
            ],
            default => [],
        };

        $orderQuery = function ($query) use ($from, $to) {
            if ($from) {
                $query->where('orders.created_at', '>=', $from);
            }
            if ($to) {
                $query->where('orders.created_at', '<=', $to);
            }
        };

        $searchQuery = function ($query) use ($request) {
            if (!$request->filled('search')) {
                return;
            }
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhereHas('processor', fn ($p) => $p->where('name', 'like', "%{$search}%"));
                $id = preg_replace('/[^0-9]/', '', $search);
                if ($id !== '') {
                    $q->orWhere('id', (int) $id);
                }
            });
        };

        $staffUsers = User::whereIn('role', ['superadmin', 'admin', 'agent'])
            ->orderBy('name')
            ->select('id', 'name', 'role')
            ->get();

        $selectedUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        if ($selectedUserId && !$staffUsers->contains('id', $selectedUserId)) {
            $selectedUserId = null;
        }

        $breakdownQuery = function ($processedBy) use ($from, $to) {
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
                'pop' => $sum('cash_on_delivery', 'pickup'),
                'bank_transfer' => $sum('bank_transfer'),
                'cash_sale' => $sum('cash_on_delivery', 'delivery'),
            ];
        };

        if ($user->isAgent()) {
            $breakdown = $breakdownQuery($user->id);
            $totalRevenue = array_sum($breakdown);
            $totalOrders = Order::where('processed_by', $user->id)
                ->whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->count();

            $agentRevenue = collect([$user->forceFill([
                'revenue' => $totalRevenue,
                'orders_count' => $totalOrders,
                'percentage' => $totalRevenue > 0 ? 100 : 0,
            ])]);

            $recentPaidOrders = Order::with('processor', 'payment')
                ->where('processed_by', $user->id)
                ->whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->when($request->filled('search'), $searchQuery)
                ->latest()
                ->paginate(15)
                ->withQueryString();

            $selectedUserId = $user->id;
            $staffUsers = collect([$user]);
        } elseif ($selectedUserId) {
            $breakdown = $breakdownQuery($selectedUserId);
            $totalRevenue = array_sum($breakdown);
            $totalOrders = Order::where('processed_by', $selectedUserId)
                ->whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->count();

            $selectedUser = $staffUsers->firstWhere('id', $selectedUserId);
            $selectedUser->revenue = $totalRevenue;
            $selectedUser->orders_count = $totalOrders;
            $selectedUser->percentage = $totalRevenue > 0 ? 100 : 0;
            $agentRevenue = collect([$selectedUser]);

            $recentPaidOrders = Order::with('processor', 'payment')
                ->where('processed_by', $selectedUserId)
                ->whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->when($request->filled('search'), $searchQuery)
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } else {
            $breakdown = $breakdownQuery(null);
            $totalRevenue = array_sum($breakdown);

            $totalOrders = Order::whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->count();

            $agentRevenue = User::whereIn('role', ['admin', 'agent'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->input('search'));
                    $q->where('name', 'like', "%{$search}%");
                })
                ->select('id', 'name', 'role')
                ->withCount(['processedOrders' => function ($q) use ($orderQuery) {
                    $q->whereHas('payment', fn($p) => $p->where('status', 'success'));
                    $orderQuery($q);
                }])
                ->withSum(['processedOrders' => function ($q) use ($orderQuery) {
                    $q->whereHas('payment', fn($p) => $p->where('status', 'success'));
                    $orderQuery($q);
                }], 'total_amount')
                ->orderByDesc('processed_orders_sum_total_amount')
                ->get()
                ->map(function ($u) use ($totalRevenue) {
                    $u->revenue = $u->processed_orders_sum_total_amount ?? 0;
                    $u->orders_count = $u->processed_orders_count ?? 0;
                    $u->percentage = $u->revenue > 0 ? round(($u->revenue / max($totalRevenue, 1)) * 100, 1) : 0;
                    return $u;
                });

            $recentPaidOrders = Order::with('processor', 'payment')
                ->whereHas('payment', fn($q) => $q->where('status', 'success'))
                ->when($from || $to, $orderQuery)
                ->when($request->filled('search'), $searchQuery)
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.revenue.index', compact(
            'totalRevenue',
            'totalOrders',
            'agentRevenue',
            'recentPaidOrders',
            'range',
            'from',
            'to',
            'staffUsers',
            'selectedUserId',
            'breakdown'
        ));
    }

    public function exportBulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('processor', 'payment')
            ->whereIn('id', $validated['ids'])
            ->whereHas('payment', fn($q) => $q->where('status', 'success'))
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment;filename=paid_orders_export.csv',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Customer', 'Processed By', 'Amount', 'Payment Method', 'Date']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->customer_name ?? 'N/A',
                    $order->processor?->name ?? 'System',
                    $order->total_amount,
                    ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')),
                    $order->created_at->format('M d, Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($orders as $order) {
            if ($validated['action'] === 'delete') {
                $order->payment()->delete();
                $order->items()->delete();
                $order->delete();
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} order(s) deleted successfully.",
        ]);
    }
}
