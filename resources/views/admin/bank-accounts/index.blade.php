@extends('layouts.admin')
@section('title', 'Bank Accounts')
@section('header', 'Bank Accounts')

@section('content')
<div x-data="bankAccountsPage()" x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })" class="max-w-5xl mx-auto">

    {{-- Success/Error Toast --}}
    <div x-show="toast" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed top-6 right-4 left-4 sm:left-auto sm:right-6 z-50 max-w-sm w-full" :class="toastType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
        <div class="flex items-center gap-3 p-4">
            <i :data-lucide="toastType === 'success' ? 'check-circle' : 'alert-circle'" class="w-5 h-5 shrink-0" :class="toastType === 'success' ? 'text-green-500' : 'text-red-500'"></i>
            <p class="text-sm font-medium" :class="toastType === 'success' ? 'text-green-800' : 'text-red-800'" x-text="toastMessage"></p>
            <button @click="toast = false" class="ml-auto shrink-0" :class="toastType === 'success' ? 'text-green-400 hover:text-green-600' : 'text-red-400 hover:text-red-600'">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="min-w-0">
            <p class="text-sm text-gray-500 mt-1 break-words">{{ $bankAccounts->count() }} bank account(s) configured</p>
        </div>
        <button @click="showForm = !showForm; editingId = null; resetForm()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm whitespace-nowrap">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Account
        </button>
    </div>

    @include('components.admin-search-filter')

    {{-- Add/Edit Form --}}
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;" x-text="editingId ? 'Edit Bank Account' : 'Add New Bank Account'"></h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bank Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.bank_name" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. Access Bank">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Account Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.account_name" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. M-Delight Singer Stores">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Account Number <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.account_number" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors font-mono" placeholder="e.g. 1234567890">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bank Code <span class="text-gray-400 font-normal">(Optional)</span></label>
                <input type="text" x-model="form.bank_code" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. 044">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sort Order</label>
                <input type="number" x-model="form.sort_order" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0">
            </div>
            <div class="flex items-center gap-3 pt-6">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                    <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </label>
                <span class="text-sm font-medium text-gray-700">Active</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-5 pt-4 border-t border-gray-100">
            <button @click="saveAccount()" :disabled="saving" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50">
                <span x-show="!saving" x-text="editingId ? 'Update Account' : 'Create Account'"></span>
                <span x-show="saving" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Saving...
                </span>
            </button>
            <button @click="showForm = false; editingId = null; resetForm()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' account(s) selected'"></span>
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

    {{-- Bank Accounts Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($bankAccounts->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i data-lucide="landmark" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Bank Accounts</h3>
                <p class="text-sm text-gray-500 mb-4">Add bank accounts so customers can make transfers during checkout.</p>
                <button @click="showForm = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add First Account
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-3 w-10 bg-gray-50">
                                <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                            </th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Bank Name</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Account Name</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Account Number <i data-lucide="lock" class="w-3 h-3 inline-block ml-1 text-gray-400"></i></th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Code</th>
                            <th class="text-center px-6 py-3 font-semibold text-gray-600">Order</th>
                            <th class="text-center px-6 py-3 font-semibold text-gray-600">Status</th>
                            <th class="text-right px-6 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($bankAccounts as $account)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 w-10">
                                <input type="checkbox" :value="{{ $account->id }}" x-model="selectedIds" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 break-words">{{ $account->bank_name }}</td>
                            <td class="px-6 py-4 text-gray-700 break-words">{{ $account->account_name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-gray-900 font-semibold text-base tracking-wider"
                                          x-text="revealed[{{ $account->id }}] ? '{{ $account->account_number }}' : '••••••••••'"></span>
                                    <button @click="toggleReveal({{ $account->id }})"
                                            class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl border-2 transition-all duration-200"
                                            :class="revealed[{{ $account->id }}]
                                                ? 'bg-red-50 text-red-600 border-red-300 hover:bg-red-100 shadow-md'
                                                : 'bg-emerald-50 text-emerald-600 border-emerald-300 hover:bg-emerald-100 shadow-md'"
                                            :title="revealed[{{ $account->id }}] ? 'Hide account number' : 'Reveal account number'">
                                        <i :data-lucide="revealed[{{ $account->id }}] ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $account->bank_code ?: '—' }}</td>
                            <td class="px-6 py-4 text-center text-gray-500">{{ $account->sort_order }}</td>
                            <td class="px-6 py-4 text-center">
                                <button @click="toggleActive({{ $account->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border cursor-pointer transition-colors {{ $account->is_active ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editAccount({{ $account->id }}, '{{ addslashes($account->bank_name) }}', '{{ addslashes($account->account_name) }}', '{{ addslashes($account->account_number) }}', '{{ addslashes($account->bank_code ?? '') }}', {{ $account->sort_order }}, {{ $account->is_active ? 'true' : 'false' }})" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="deleteAccount({{ $account->id }}, '{{ addslashes($account->bank_name) }}')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
function bankAccountsPage() {
    return {
        showForm: false,
        saving: false,
        editingId: null,
        toast: false,
        toastMessage: '',
        toastType: 'success',
        revealed: {},
        form: {
            bank_name: '',
            account_name: '',
            account_number: '',
            bank_code: '',
            sort_order: 0,
            is_active: true,
        },

        resetForm() {
            this.form = { bank_name: '', account_name: '', account_number: '', bank_code: '', sort_order: 0, is_active: true };
        },

        toggleReveal(id) {
            this.revealed[id] = !this.revealed[id];
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        showToast(message, type) {
            this.toastMessage = message;
            this.toastType = type || 'success';
            this.toast = true;
            setTimeout(() => { this.toast = false; }, 3000);
        },

        editAccount(id, bankName, accountName, accountNumber, bankCode, sortOrder, isActive) {
            this.editingId = id;
            this.form = {
                bank_name: bankName,
                account_name: accountName,
                account_number: accountNumber,
                bank_code: bankCode,
                sort_order: sortOrder,
                is_active: isActive
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async toggleActive(id) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch('/admin/bank-accounts/' + id + '/toggle', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.reload();
                }
            } catch (e) {
                this.showToast('Error toggling status.', 'error');
            }
        },

        async deleteAccount(id, name) {
            if (!confirm('Are you sure you want to delete the bank account for "' + name + '"? This cannot be undone.')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch('/admin/bank-accounts/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.reload();
                }
            } catch (e) {
                this.showToast('Error deleting account.', 'error');
            }
        },

        async saveAccount() {
            if (!this.form.bank_name || !this.form.account_name || !this.form.account_number) {
                this.showToast('Please fill in all required fields.', 'error');
                return;
            }
            this.saving = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                let url, method;
                if (this.editingId) {
                    url = '/admin/bank-accounts/' + this.editingId;
                    method = 'PUT';
                } else {
                    url = '/admin/bank-accounts';
                    method = 'POST';
                }
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message);
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    this.showToast(data.message || 'Something went wrong.', 'error');
                }
            } catch (e) {
                this.showToast('Network error. Please try again.', 'error');
            } finally {
                this.saving = false;
            }
        },

        selectedIds: [],
        allSelected: false,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = @js($bankAccounts->pluck('id')->toArray());
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async bulkAction(action) {
            const labels = { delete: 'Delete', activate: 'Activate', deactivate: 'Deactivate' };
            const msg = action === 'delete'
                ? 'Delete selected bank accounts? This cannot be undone.'
                : labels[action] + ' selected accounts?';
            if (!confirm(msg)) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch('{{ route("admin.bank-accounts.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action, ids: this.selectedIds }),
                });
                const data = await res.json();
                if (data.success) window.location.reload();
                else alert(data.message || 'An error occurred.');
            } catch (e) {
                alert('An error occurred. Please try again.');
            }
        },
    }
}
</script>
@endsection
