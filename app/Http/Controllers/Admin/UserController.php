<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::whereIn('role', ['superadmin', 'admin', 'agent'])
            ->withCount(['processedOrders'])
            ->withSum(['processedOrders as total_revenue' => function ($q) {
                $q->whereHas('payment', fn($q) => $q->where('status', 'success'));
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

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => null]);
    }

    public function store(Request $request)
    {
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
            'permissions.restore_cancelled_orders' => 'boolean',
        ]);

        if ($data['role'] === 'superadmin' && !auth()->user()->isSuperadmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Only a superadmin can create another superadmin.');
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

            if (!auth()->user()->isSuperadmin()) {
                $data['permissions']['manage_users'] = false;
            }
        }

        $user = User::create($data);
        // Staff accounts created by an admin are considered verified.
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        if ($user->isSuperadmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot edit superadmin.');
        }

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSuperadmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot edit superadmin.');
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
            'permissions.restore_cancelled_orders' => 'boolean',
        ]);

        $data['email'] = Str::lower($data['email']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($user->id === auth()->id() && !$data['is_active']) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($request->has('permissions')) {
            $defaults = (new User(['role' => $data['role']]))->getDefaultPermissions();
            $submitted = $request->input('permissions', []);
            $data['permissions'] = array_merge($defaults, $submitted);

            if (!auth()->user()->isSuperadmin()) {
                $data['permissions']['manage_users'] = $user->permissions['manage_users'] ?? false;
            }
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperadmin() || $user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete this user.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function sendPasswordReset(User $user)
    {
        if ($user->isSuperadmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot send reset link for superadmin.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        $message = $status === Password::RESET_LINK_SENT
            ? 'Password reset link sent to ' . $user->email
            : 'Failed to send reset link. Please try again.';

        return redirect()->route('admin.users.index')
            ->with($status === Password::RESET_LINK_SENT ? 'success' : 'error', $message);
    }

    public function recover($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        if ($user->isSuperadmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot recover superadmin.');
        }

        $user->update(['is_active' => true]);

        if ($user->trashed()) {
            $user->restore();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User account recovered successfully.');
    }

    public function bulk(Request $request)
    {
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

            if ($validated['action'] === 'delete') {
                $user->delete();
                $count++;
            } elseif ($validated['action'] === 'activate') {
                $user->update(['is_active' => true]);
                $count++;
            } elseif ($validated['action'] === 'deactivate') {
                $user->update(['is_active' => false]);
                $count++;
            }
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];
        return response()->json([
            'success' => true,
            'message' => "{$count} user(s) {$labels[$validated['action']]} successfully.",
        ]);
    }
}