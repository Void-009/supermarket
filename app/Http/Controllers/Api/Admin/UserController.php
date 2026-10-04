<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        $users = User::whereIn('role', ['superadmin', 'admin', 'agent'])
            ->withCount(['processedOrders'])
            ->withSum(['processedOrders as total_revenue' => function ($q) {
                $q->whereHas('payment', fn ($q) => $q->where('status', 'success'));
            }], 'total_amount')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                $q->where(function ($b) use ($search) {
                    $b->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'users' => collect($users->items())->map(fn (User $u) => $this->presentUser($u)),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        $user->loadCount('processedOrders');
        $user->loadSum(['processedOrders as total_revenue' => function ($q) {
            $q->whereHas('payment', fn ($q) => $q->where('status', 'success'));
        }], 'total_amount');

        return response()->json([
            'success' => true,
            'user' => $this->presentUser($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', PasswordRule::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:superadmin,admin,agent',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.process_orders' => 'boolean',
            'permissions.modify_items' => 'boolean',
            'permissions.manage_categories' => 'boolean',
            'permissions.manage_delivery_locations' => 'boolean',
            'permissions.manage_bank_accounts' => 'boolean',
            'permissions.manage_payment_settings' => 'boolean',
            'permissions.view_sync' => 'boolean',
            'permissions.view_revenue' => 'boolean',
            'permissions.manage_complaints' => 'boolean',
            'permissions.manage_discounts' => 'boolean',
            'permissions.manage_chat' => 'boolean',
            'permissions.manage_users' => 'boolean',
            'permissions.reverse_orders' => 'boolean',
        ]);

        if ($data['role'] === 'superadmin' && ! auth()->user()->isSuperadmin()) {
            return response()->json(['success' => false, 'message' => 'Only a superadmin can create another superadmin.'], 403);
        }

        $data['email'] = Str::lower($data['email']);
        $data['password'] = bcrypt($data['password']);
        $data['is_active'] = $request->boolean('is_active');

        if ($data['role'] === 'superadmin') {
            $data['permissions'] = (new User(['role' => 'superadmin']))->getDefaultPermissions();
        } else {
            $defaults = (new User(['role' => $data['role']]))->getDefaultPermissions();
            $submitted = $request->input('permissions', []);
            $data['permissions'] = array_merge($defaults, $submitted);

            if (! auth()->user()->isSuperadmin()) {
                $data['permissions']['manage_users'] = false;
            }
        }

        $user = User::create($data);
        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'user' => $this->presentUser($user->fresh()),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        if ($user->isSuperadmin()) {
            return response()->json(['success' => false, 'message' => 'Cannot edit superadmin.'], 422);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', PasswordRule::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,agent',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.process_orders' => 'boolean',
            'permissions.modify_items' => 'boolean',
            'permissions.manage_categories' => 'boolean',
            'permissions.manage_delivery_locations' => 'boolean',
            'permissions.manage_bank_accounts' => 'boolean',
            'permissions.manage_payment_settings' => 'boolean',
            'permissions.view_sync' => 'boolean',
            'permissions.view_revenue' => 'boolean',
            'permissions.manage_complaints' => 'boolean',
            'permissions.manage_discounts' => 'boolean',
            'permissions.manage_chat' => 'boolean',
            'permissions.manage_users' => 'boolean',
            'permissions.reverse_orders' => 'boolean',
        ]);

        $data['email'] = Str::lower($data['email']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($user->id === auth()->id() && ! $data['is_active']) {
            return response()->json(['success' => false, 'message' => 'You cannot deactivate your own account.'], 422);
        }

        if ($request->has('permissions')) {
            $defaults = (new User(['role' => $data['role']]))->getDefaultPermissions();
            $submitted = $request->input('permissions', []);
            $data['permissions'] = array_merge($defaults, $submitted);

            if (! auth()->user()->isSuperadmin()) {
                $data['permissions']['manage_users'] = $user->permissions['manage_users'] ?? false;
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => $this->presentUser($user->fresh()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        if ($user->isSuperadmin() || $user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete this user.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    public function sendResetLink(User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        if ($user->isSuperadmin()) {
            return response()->json(['success' => false, 'message' => 'Cannot send reset link for superadmin.'], 422);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        return response()->json([
            'success' => $status === Password::RESET_LINK_SENT,
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Password reset link sent to ' . $user->email
                : 'Failed to send reset link. Please try again.',
        ]);
    }

    public function recover($userId): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        $user = User::withTrashed()->findOrFail($userId);

        if ($user->isSuperadmin()) {
            return response()->json(['success' => false, 'message' => 'Cannot recover superadmin.'], 422);
        }

        $user->update(['is_active' => true]);

        if ($user->trashed()) {
            $user->restore();
        }

        return response()->json([
            'success' => true,
            'message' => 'User account recovered successfully.',
            'user' => $this->presentUser($user->fresh()),
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_users'), 403, 'You do not have permission to manage users.');

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($users as $user) {
            if ($user->isSuperadmin() || $user->id === auth()->id()) {
                continue;
            }

            match ($validated['action']) {
                'delete' => $user->delete(),
                'activate' => $user->update(['is_active' => true]),
                'deactivate' => $user->update(['is_active' => false]),
            };
            $count++;
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];

        return response()->json([
            'success' => true,
            'message' => "{$count} user(s) {$labels[$validated['action']]} successfully.",
        ]);
    }

    private function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
            'permissions' => $user->permissions,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'processed_orders_count' => $user->processed_orders_count ?? 0,
            'total_revenue' => round((float) ($user->total_revenue ?? 0), 2),
            'deleted_at' => $user->deleted_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
