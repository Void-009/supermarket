<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\DiscountCampaign;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        $type = $request->input('type', '');

        if (strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();

        $results = match ($type) {
            'categories' => $this->categories($q, $user),
            'orders' => $this->orders($q, $user),
            'users' => $this->users($q, $user),
            'complaints' => $this->complaints($q, $user),
            'delivery-locations' => $this->deliveryLocations($q, $user),
            'bank-accounts' => $this->bankAccounts($q, $user),
            'discount-campaigns' => $this->discountCampaigns($q, $user),
            'revenue' => $this->revenue($q, $user),
            'sync' => $this->sync($q, $user),
            'pos-sync' => $this->posSync($q, $user),
            default => [],
        };

        return response()->json(['results' => $results]);
    }

    private function categories(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_categories')) {
            return [];
        }

        return Category::where(function ($b) use ($q) {
            $b->where('name', 'like', "%{$q}%")
              ->orWhere('slug', 'like', "%{$q}%")
              ->orWhere('description', 'like', "%{$q}%");
        })
        ->limit(8)
        ->get()
        ->map(fn ($c) => [
            'name' => $c->name,
            'subtitle' => $c->slug,
            'url' => route('admin.categories.index', ['search' => $c->name]),
        ])
        ->all();
    }

    private function orders(string $q, User $user): array
    {
        if (!$user->hasPermission('process_orders')) {
            return [];
        }

        $id = preg_replace('/[^0-9]/', '', $q);

        $query = Order::with('processor')
            ->where(function ($b) use ($q, $id) {
                $b->where('customer_name', 'like', "%{$q}%")
                  ->orWhere('customer_phone', 'like', "%{$q}%")
                  ->orWhere('customer_email', 'like', "%{$q}%")
                  ->orWhere('payment_reference', 'like', "%{$q}%")
                  ->orWhereHas('processor', fn ($p) => $p->where('name', 'like', "%{$q}%"));
                if ($id !== '') {
                    $b->orWhere('id', (int) $id);
                }
            });

        if ($user->isAgent()) {
            $query->where(function ($b) use ($user) {
                $b->where('processed_by', $user->id)
                  ->orWhere('status', 'new');
            });
        }

        return $query
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($o) => [
                'name' => '#' . $o->id . ' — ' . ($o->customer_name ?? 'Guest'),
                'subtitle' => ucfirst($o->status) . ' · ' . $o->created_at->format('M d, Y'),
                'url' => route('admin.orders.index', ['search' => $o->id]),
            ])
            ->all();
    }

    private function users(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_users')) {
            return [];
        }

        return User::whereIn('role', ['superadmin', 'admin', 'agent'])
            ->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'subtitle' => $u->email ?? $u->role,
                'url' => route('admin.users.index', ['search' => $u->name]),
            ])
            ->all();
    }

    private function complaints(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_complaints')) {
            return [];
        }

        $id = preg_replace('/[^0-9]/', '', $q);

        return OrderComplaint::with('user')
            ->where(function ($b) use ($q, $id) {
                $b->where('subject', 'like', "%{$q}%")
                  ->orWhere('message', 'like', "%{$q}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                if ($id !== '') {
                    $b->orWhere('id', (int) $id);
                }
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'name' => $c->subject ?? 'Complaint #' . $c->id,
                'subtitle' => ($c->user?->name ?? 'Guest') . ' · ' . ucfirst($c->status),
                'url' => route('admin.complaints.index', ['search' => $c->id]),
            ])
            ->all();
    }

    private function deliveryLocations(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_delivery_locations')) {
            return [];
        }

        return DeliveryLocation::where(function ($b) use ($q) {
            $b->where('name', 'like', "%{$q}%")
              ->orWhere('description', 'like', "%{$q}%");
        })
        ->orderBy('sort_order')
        ->orderBy('name')
        ->limit(8)
        ->get()
        ->map(fn ($l) => [
            'name' => $l->name,
            'subtitle' => '₦' . number_format((float) $l->cost, 2),
            'url' => route('admin.delivery-locations.index', ['search' => $l->name]),
        ])
        ->all();
    }

    private function bankAccounts(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_bank_accounts')) {
            return [];
        }

        return BankAccount::where(function ($b) use ($q) {
            $b->where('bank_name', 'like', "%{$q}%")
              ->orWhere('account_name', 'like', "%{$q}%")
              ->orWhere('account_number', 'like', "%{$q}%");
        })
        ->orderBy('sort_order')
        ->orderBy('bank_name')
        ->limit(8)
        ->get()
        ->map(fn ($b) => [
            'name' => $b->account_name,
            'subtitle' => $b->bank_name . ' · ' . $b->account_number,
            'url' => route('admin.bank-accounts.index', ['search' => $b->account_number]),
        ])
        ->all();
    }

    private function discountCampaigns(string $q, User $user): array
    {
        if (!$user->hasPermission('manage_discounts')) {
            return [];
        }

        return DiscountCampaign::where(function ($b) use ($q) {
            $b->where('name', 'like', "%{$q}%")
              ->orWhere('description', 'like', "%{$q}%");
        })
        ->latest()
        ->limit(8)
        ->get()
        ->map(fn ($c) => [
            'name' => $c->name,
            'subtitle' => $c->discount_percentage . '% off' . ($c->is_active ? ' · Active' : ' · Inactive'),
            'url' => route('admin.discount-campaigns.index', ['search' => $c->name]),
        ])
        ->all();
    }

    private function revenue(string $q, User $user): array
    {
        if (!$user->hasPermission('view_revenue')) {
            return [];
        }

        $id = preg_replace('/[^0-9]/', '', $q);

        $agents = User::whereIn('role', ['admin', 'agent'])
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'subtitle' => ucfirst($u->role) . ' · Revenue breakdown',
                'url' => route('admin.revenue.index', ['search' => $u->name]),
            ]);

        $orders = Order::with('processor')
            ->whereHas('payment', fn ($p) => $p->where('status', 'success'))
            ->where(function ($b) use ($q, $id) {
                $b->where('customer_name', 'like', "%{$q}%")
                  ->orWhere('customer_phone', 'like', "%{$q}%")
                  ->orWhereHas('processor', fn ($p) => $p->where('name', 'like', "%{$q}%"));
                if ($id !== '') {
                    $b->orWhere('id', (int) $id);
                }
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'name' => '#' . $o->id . ' — ' . ($o->customer_name ?? 'Guest'),
                'subtitle' => '₦' . number_format((float) $o->total_amount, 2) . ' · Paid order',
                'url' => route('admin.revenue.index', ['search' => $o->id]),
            ]);

        return $agents->concat($orders)->take(8)->all();
    }

    private function posSync(string $q, User $user): array
    {
        if (!$user->hasPermission('view_sync')) {
            return [];
        }

        $id = preg_replace('/[^0-9]/', '', $q);

        return \App\Models\PosSyncBatch::where(function ($b) use ($q, $id) {
            $b->where('sync_id', 'like', "%{$q}%")
              ->orWhere('source', 'like', "%{$q}%")
              ->orWhere('status', 'like', "%{$q}%");
            if ($id !== '') {
                $b->orWhere('id', (int) $id);
            }
        })
        ->latest()
        ->limit(8)
        ->get()
        ->map(fn ($l) => [
            'name' => 'POS sync #' . $l->id,
            'subtitle' => ucfirst($l->status) . ' · ' . $l->total_items . ' item(s) · ' . ($l->created_at?->format('M d, Y') ?? 'N/A'),
            'url' => route('admin.pos-sync.show', $l),
        ])
        ->all();
    }

    private function sync(string $q, User $user): array
    {
        if (!$user->hasPermission('view_sync')) {
            return [];
        }

        $id = preg_replace('/[^0-9]/', '', $q);

        return SyncLog::where(function ($b) use ($q, $id) {
            $b->where('file_name', 'like', "%{$q}%")
              ->orWhere('mode', 'like', "%{$q}%")
              ->orWhere('status', 'like', "%{$q}%");
            if ($id !== '') {
                $b->orWhere('id', (int) $id);
            }
        })
        ->latest()
        ->limit(8)
        ->get()
        ->map(fn ($l) => [
            'name' => ($l->file_name ?? 'Sync') . ' #' . $l->id,
            'subtitle' => ucfirst($l->status) . ' · ' . ($l->created_at?->format('M d, Y') ?? 'N/A'),
            'url' => route('admin.sync.index', ['search' => $l->id]),
        ])
        ->all();
    }
}
