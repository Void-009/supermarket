@extends('layouts.admin')

@section('title', 'Users')
@section('header', 'Users')

@section('content')
<div x-data="bulkManager()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Users & Agents</h2>
            <p class="text-sm text-gray-500 mt-1">Manage users, agents, and their permissions.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add User
        </a>
    </div>

    @include('components.admin-search-filter')

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' user(s) selected'"></span>
            <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="bulkAction('activate')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 rounded-lg transition-colors">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Activate
            </button>
            <button @click="bulkAction('deactivate')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-lg transition-colors">
                <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i> Deactivate
            </button>
            <button @click="bulkAction('delete')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-fixed min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">User</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 hidden md:table-cell">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 hidden lg:table-cell">Orders</th>
                        @if(Auth::user()->hasPermission('view_revenue'))
                        <th class="px-4 py-3 text-right font-medium text-gray-500 hidden lg:table-cell">Revenue</th>
                        @endif
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3 w-10">
                            <input type="checkbox" :value="{{ $u->id }}" x-model="selectedIds" @change="allSelected = selectedIds.length === {{ $users->total() }}" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold uppercase flex-shrink-0">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 truncate">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-500 truncate md:hidden">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 hidden md:table-cell break-all">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            @php
                                $roleColors = [
                                    'superadmin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'admin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'agent' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $roleColors[$u->role] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ ucfirst($u->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-medium text-gray-900 hidden lg:table-cell whitespace-nowrap">{{ number_format($u->processed_orders_count) }}</td>
                        @if(Auth::user()->hasPermission('view_revenue'))
                        <td class="px-4 py-3 text-right font-medium text-gray-900 hidden lg:table-cell whitespace-nowrap">₦{{ number_format($u->total_revenue ?? 0, 2) }}</td>
                        @endif
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($u->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(!$u->isSuperadmin())
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    Actions <i data-lucide="chevron-down" class="w-3 h-3" :class="open && 'rotate-180'"></i>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="absolute right-0 mt-1 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-50">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                                        <i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i> Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.users.send-reset-link', $u) }}" class="block">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors text-left">
                                            <i data-lucide="key" class="w-4 h-4 text-gray-400"></i> Send Reset Link
                                        </button>
                                    </form>
                                    @if($u->is_active)
                                    <form method="POST" action="{{ route('admin.users.update', $u) }}" class="block">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" value="{{ $u->name }}">
                                        <input type="hidden" name="email" value="{{ $u->email }}">
                                        <input type="hidden" name="role" value="{{ $u->role }}">
                                        <input type="hidden" name="is_active" value="0">
                                        <button type="submit" onclick="return confirm('Deactivate this user?')" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-amber-700 hover:bg-amber-50 transition-colors text-left">
                                            <i data-lucide="pause-circle" class="w-4 h-4 text-amber-500"></i> Deactivate
                                        </button>
                                    </form>
                                    @else
                                    <form method="POST" action="{{ route('admin.users.recover', $u) }}" class="block">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-emerald-700 hover:bg-emerald-50 transition-colors text-left">
                                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i> Recover Account
                                        </button>
                                    </form>
                                    @endif
                                    <hr class="my-1 border-gray-100">
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-700 hover:bg-red-50 transition-colors text-left">
                                            <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ Auth::user()->hasPermission('view_revenue') ? 8 : 7 }}" class="px-6 py-12 text-center">
                            <i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No users found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $users->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
function bulkManager() {
    return {
        selectedIds: [],
        allSelected: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = @js($users->pluck('id')->toArray());
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async bulkAction(action) {
            const labels = { delete: 'Delete', activate: 'Activate', deactivate: 'Deactivate' };
            const msg = action === 'delete'
                ? 'Delete selected users? Superadmins and your own account will be skipped. This cannot be undone.'
                : labels[action] + ' selected users?';
            if (!confirm(msg)) return;
            try {
                const res = await fetch('{{ route("admin.users.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action, ids: this.selectedIds }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred.');
                }
            } catch (e) {
                alert('An error occurred. Please try again.');
            }
        },
    };
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
