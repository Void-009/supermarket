@extends('layouts.admin')

@section('title', $user ? 'Edit User' : 'Create User')
@section('header', $user ? 'Edit User' : 'Create User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-4 sm:px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">
                    {{ $user ? 'Edit User' : 'Create New User' }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $user ? 'Update user details and permissions.' : 'Create a new admin or agent account.' }}
                </p>
            </div>
        </div>

        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="p-4 sm:p-6 space-y-6">
            @csrf
            @if($user)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Full name">
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Email address">
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Password @if(!$user)<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="password" name="password" {{ $user ? '' : 'required' }}
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                        placeholder="{{ $user ? 'Leave blank to keep current' : 'Min 8 characters' }}">
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Phone number">
                    @error('phone')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                    <select name="role" id="role-select" required
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        @if(!$user && Auth::user()->isSuperadmin())
                        <option value="superadmin" {{ old('role') === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                        <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="agent" {{ old('role', $user->role ?? '') === 'agent' ? 'selected' : '' }}>Agent</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center gap-3 mt-6 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h4 class="text-base font-semibold text-gray-900 mb-1" style="font-family:'Poppins',sans-serif;">Permissions</h4>
                <p class="text-sm text-gray-500 mb-4">Assign specific permissions to this user.</p>

                <div id="superadmin-perm-note" class="hidden mb-4 p-3 bg-purple-50 border border-purple-200 rounded-xl text-sm text-purple-700">
                    <i data-lucide="shield-check" class="w-4 h-4 inline-block mr-1"></i>
                    Super Admins automatically receive <strong>all permissions</strong> — no manual assignment needed.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="permissions-grid">
                    @php
                        $perms = old('permissions', $user?->permissions ?? $user?->getDefaultPermissions() ?? []);
                    @endphp
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[process_orders]" value="1"
                            {{ ($perms['process_orders'] ?? true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Process Orders</span>
                            <p class="text-xs text-gray-400">Can view, update status, and manage orders</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[modify_items]" value="1"
                            {{ ($perms['modify_items'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Modify Items</span>
                            <p class="text-xs text-gray-400">Can add, edit, and delete inventory items</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_categories]" value="1"
                            {{ ($perms['manage_categories'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Categories</span>
                            <p class="text-xs text-gray-400">Can create and manage product categories</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[view_sync]" value="1"
                            {{ ($perms['view_sync'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">View Sync</span>
                            <p class="text-xs text-gray-400">Can view and trigger data synchronization</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_bank_accounts]" value="1"
                            {{ ($perms['manage_bank_accounts'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Bank Accounts</span>
                            <p class="text-xs text-gray-400">Can create and manage bank account details</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[view_revenue]" value="1"
                            {{ ($perms['view_revenue'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">View Revenue</span>
                            <p class="text-xs text-gray-400">Can see revenue data on dashboard and reports</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_delivery_locations]" value="1"
                            {{ ($perms['manage_delivery_locations'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Delivery Locations</span>
                            <p class="text-xs text-gray-400">Can manage delivery zones and settings</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_payment_settings]" value="1"
                            {{ ($perms['manage_payment_settings'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Payment Settings</span>
                            <p class="text-xs text-gray-400">Can configure payment and delivery fee settings</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_complaints]" value="1"
                            {{ ($perms['manage_complaints'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Complaints</span>
                            <p class="text-xs text-gray-400">Can view and respond to customer complaints</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_discounts]" value="1"
                            {{ ($perms['manage_discounts'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Discounts</span>
                            <p class="text-xs text-gray-400">Can create and manage discount campaigns</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_chat]" value="1"
                            {{ ($perms['manage_chat'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Chat</span>
                            <p class="text-xs text-gray-400">Can chat with customers and manage conversations</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[manage_users]" value="1"
                            {{ ($perms['manage_users'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Manage Users</span>
                            <p class="text-xs text-gray-400">Can create, edit, activate, and delete staff accounts</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="permissions[reverse_orders]" value="1"
                            {{ ($perms['reverse_orders'] ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Reverse Paid Orders</span>
                            <p class="text-xs text-gray-400">Can reverse orders back from paid, shipped, or delivered</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
                    {{ $user ? 'Update User' : 'Create User' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role-select');
    if (!roleSelect) return;

    const permBoxes = Array.from(document.querySelectorAll('input[name^="permissions["]'));
    const permGrid = document.getElementById('permissions-grid');
    const permNote = document.getElementById('superadmin-perm-note');
    const original = permBoxes.map(cb => cb.checked);

    function syncPermissions() {
        const isSuper = roleSelect.value === 'superadmin';
        permBoxes.forEach((cb, i) => {
            cb.checked = isSuper ? true : original[i];
            cb.disabled = isSuper;
            cb.classList.toggle('opacity-50', isSuper);
        });
        if (permGrid) permGrid.classList.toggle('opacity-60', isSuper);
        if (permNote) permNote.classList.toggle('hidden', !isSuper);
    }

    roleSelect.addEventListener('change', syncPermissions);
    syncPermissions();
});
</script>
@endsection