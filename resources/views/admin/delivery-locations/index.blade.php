@extends('layouts.admin')

@section('title', 'Delivery Locations')
@section('header', 'Delivery Locations')

@section('content')
<div x-data="deliveryLocationsManager()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Delivery Locations</h2>
            <p class="text-sm text-gray-500 mt-1">Manage delivery zones and their associated costs.</p>
        </div>
        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm shrink-0 whitespace-nowrap">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Location
        </button>
    </div>

    @include('components.admin-search-filter')

    @if(!$feeSelectorEnabled)
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
        <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 shrink-0"></i>
        <div>
            <p class="text-sm font-semibold text-blue-800">Delivery fee selection is currently disabled for customers.</p>
            <p class="text-xs text-blue-600 mt-0.5">Customers will be asked to contact an agent for delivery fee. <a href="{{ route('admin.delivery-settings.edit') }}" class="underline font-medium">Manage in Delivery Settings</a></p>
        </div>
    </div>
    @endif

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' location(s) selected'"></span>
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

    {{-- Locations Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event, {{ $locations->total() }})" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 whitespace-nowrap">Standard</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 whitespace-nowrap">Motorcycle</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 whitespace-nowrap">Truck</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500">Orders</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500">Status</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500">Sort</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($locations as $location)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-3 py-3 w-10">
                            <input type="checkbox" :value="{{ $location->id }}" x-model="selectedIds" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-3 py-3 min-w-0">
                            <div class="font-medium text-gray-900 truncate max-w-[14rem]">{{ $location->name }}</div>
                            @if($location->description)
                                <div class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $location->description }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="font-semibold text-gray-900">₦{{ number_format($location->cost, 2) }}</span>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-700">₦{{ number_format($location->motorcycle_rate, 2) }}</span>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-700">₦{{ number_format($location->truck_rate, 2) }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                {{ $location->orders_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if($location->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">Inactive</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center text-gray-500 whitespace-nowrap">{{ $location->sort_order }}</td>
                        <td class="px-3 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="openEditModal(@js($location))" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                                </button>
                                <button @click="deleteLocation({{ $location->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <i data-lucide="map-pin" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No delivery locations yet.</p>
                            <p class="text-xs text-gray-400 mt-1">Add your first delivery location to get started.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $locations->links('components.pagination') }}
        </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-4 sm:px-6 py-4 flex items-center justify-between rounded-t-2xl z-10">
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;" x-text="editingLocation ? 'Edit Location' : 'Add Location'"></h3>
                <button @click="closeModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form @submit.prevent="saveLocation()" class="p-4 sm:p-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Location Name <span class="text-red-500">*</span></label>
                    <input x-model="form.name" type="text" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. Downtown Core">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Cost (₦) <span class="text-red-500">*</span></label>
                    <input x-model="form.cost" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0.00">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Motorcycle Rate (₦)</label>
                        <input x-model="form.motorcycle_rate" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-1">For bulk/bulky orders via motorcycle</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Truck Rate (₦)</label>
                        <input x-model="form.truck_rate" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-1">For bulk/bulky orders via truck</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                    <textarea x-model="form.description" rows="2" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="Optional description..."></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sort Order</label>
                        <input x-model="form.sort_order" type="number" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer pb-2.5">
                            <div class="relative">
                                <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-show="!saving" x-text="editingLocation ? 'Update Location' : 'Save Location'"></span>
                        <span x-show="saving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation --}}
    <div x-show="showDeleteConfirm" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteConfirm = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-4 sm:p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Location?</h3>
            <p class="text-sm text-gray-500 mb-6">This action cannot be undone. Existing orders using this location will keep their data.</p>
            <div class="flex items-center gap-3">
                <button @click="showDeleteConfirm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                <button @click="confirmDelete()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    function deliveryLocationsManager() {
        return {
            showModal: false,
            showDeleteConfirm: false,
            editingLocation: null,
            deleteId: null,
            saving: false,
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            form: {
                name: '',
                cost: '',
                motorcycle_rate: 0,
                truck_rate: 0,
                description: '',
                is_active: true,
                sort_order: 0,
            },

            openCreateModal() {
                this.editingLocation = null;
                this.form = { name: '', cost: '', motorcycle_rate: 0, truck_rate: 0, description: '', is_active: true, sort_order: 0 };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            openEditModal(location) {
                this.editingLocation = location;
                this.form = {
                    name: location.name,
                    cost: location.cost,
                    motorcycle_rate: location.motorcycle_rate || 0,
                    truck_rate: location.truck_rate || 0,
                    description: location.description || '',
                    is_active: location.is_active,
                    sort_order: location.sort_order || 0,
                };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            closeModal() {
                this.showModal = false;
                this.editingLocation = null;
            },

            async saveLocation() {
                this.saving = true;
                try {
                    const url = this.editingLocation
                        ? `/admin/delivery-locations/${this.editingLocation.id}`
                        : '/admin/delivery-locations';
                    const method = this.editingLocation ? 'PUT' : 'POST';

                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                } catch (err) {
                    alert('An error occurred. Please try again.');
                }
                this.saving = false;
            },

            deleteLocation(id) {
                this.deleteId = id;
                this.showDeleteConfirm = true;
                this.$nextTick(() => lucide.createIcons());
            },

            async confirmDelete() {
                try {
                    const response = await fetch(`/admin/delivery-locations/${this.deleteId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (data.success) window.location.reload();
                } catch (err) {
                    window.location.reload();
                }
                this.showDeleteConfirm = false;
            },
            selectedIds: [],
            allSelected: false,

            toggleAll(e, total) {
                if (e.target.checked) {
                    this.selectedIds = @js($locations->pluck('id')->toArray());
                    this.allSelected = true;
                } else {
                    this.selectedIds = [];
                    this.allSelected = false;
                }
            },

            async bulkAction(action) {
                const labels = { delete: 'Delete', activate: 'Activate', deactivate: 'Deactivate' };
                const msg = action === 'delete'
                    ? 'Delete selected delivery locations? Existing orders will keep their data.'
                    : labels[action] + ' selected locations?';
                if (!confirm(msg)) return;
                try {
                    const res = await fetch('{{ route("admin.delivery-locations.bulk") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
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
        };
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
