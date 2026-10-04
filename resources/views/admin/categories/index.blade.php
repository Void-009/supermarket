@extends('layouts.admin')

@section('title', 'Categories')
@section('header', 'Categories')

@section('content')
<div x-data="categoriesManager()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Categories</h2>
            <p class="text-sm text-gray-500 mt-1">Organize your products into categories.</p>
        </div>
        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Category
        </button>
    </div>

    @include('components.admin-search-filter')

    {{-- Sort Tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <a href="{{ route('admin.categories.index', ['sort' => 'newest']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ ($sort ?? 'newest') === 'newest' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <i data-lucide="clock" class="w-4 h-4"></i> Newest First
        </a>
        <a href="{{ route('admin.categories.index', ['sort' => 'popular']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ ($sort ?? '') === 'popular' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <i data-lucide="trending-up" class="w-4 h-4"></i> Most Popular
        </a>
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' category(ies) selected'"></span>
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

    {{-- Categories Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event, {{ $categories->total() }})" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Slug</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Items</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Orders</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($categories as $category)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 w-10">
                            <input type="checkbox" :value="{{ $category->id }}" x-model="selectedIds" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $category->name }}</div>
                            @if($category->description)
                                <div class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $category->slug }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                {{ $category->items_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($category->ordered_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500 border border-gray-200' }}">
                                {{ $category->ordered_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($category->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">Inactive</span>
                                @endif
                                @if($category->show_on_home)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">Home</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEditModal(@js($category), @js($category->images))" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                                </button>
                                <button @click="deleteCategory({{ $category->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="grid-3x3" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No categories yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $categories->links('components.pagination') }}
        </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-4 sm:px-6 py-4 flex items-center justify-between gap-3 rounded-t-2xl z-10">
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;" x-text="editingCategory ? 'Edit Category' : 'Add Category'"></h3>
                <button @click="closeModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form @submit.prevent="saveCategory()" class="p-4 sm:p-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input x-model="form.name" type="text" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Category name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                    <textarea x-model="form.description" rows="3" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="Optional description..."></textarea>
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
                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer pb-2.5">
                        <div class="relative">
                            <input type="checkbox" x-model="form.show_on_home" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Show on Home</span>
                            <p class="text-[11px] text-gray-400 leading-tight">Show this category on the store home page</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Category Images (only available when editing an existing category) --}}
            <template x-if="editingCategory">
                <div class="border-t border-gray-100 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">Category Images</label>
                        <span class="text-xs text-gray-400">Upload banners shown on the category page</span>
                    </div>

                    {{-- Upload --}}
                    <div class="flex items-center gap-3 mb-4">
                        <input type="file" accept="image/*" multiple x-ref="imageInput" class="block w-full min-w-0 text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                        <button type="button" @click="uploadImages()" :disabled="uploading" class="shrink-0 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors disabled:opacity-50">
                            <span x-show="!uploading">Upload</span>
                            <span x-show="uploading" x-cloak>Uploading...</span>
                        </button>
                    </div>

                    {{-- Thumbnails --}}
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3" x-show="images.length">
                        <template x-for="img in images" :key="img.id">
                            <div class="relative group rounded-xl overflow-hidden border border-gray-200 aspect-square bg-gray-50">
                                <img :src="'/storage/' + img.path" :alt="editingCategory?.name" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1.5 p-1">
                                    <button type="button" @click="setPrimary(img.id)" x-show="!img.is_primary" class="text-[10px] px-2 py-1 bg-white/90 text-emerald-700 rounded font-semibold">Set Primary</button>
                                    <span x-show="img.is_primary" class="text-[10px] px-2 py-1 bg-emerald-500 text-white rounded font-semibold">Primary</span>
                                    <button type="button" @click="deleteImage(img.id)" class="text-[10px] px-2 py-1 bg-red-500 text-white rounded font-semibold">Delete</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="!images.length" class="text-xs text-gray-400">No images yet. Upload some to display on the shop category page.</p>
                </div>
            </template>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-show="!saving" x-text="editingCategory ? 'Update Category' : 'Save Category'"></span>
                        <span x-show="saving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation --}}
    <div x-show="showDeleteConfirm" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteConfirm = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Category?</h3>
            <p class="text-sm text-gray-500 mb-6">This action cannot be undone. Items in this category will become uncategorized.</p>
            <div class="flex items-center gap-3">
                <button @click="showDeleteConfirm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                <button @click="confirmDelete()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    function categoriesManager() {
        return {
            showModal: false,
            showDeleteConfirm: false,
            editingCategory: null,
            deleteId: null,
            saving: false,
            uploading: false,
            images: [],
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            form: {
                name: '',
                description: '',
                is_active: true,
                show_on_home: true,
                sort_order: 0,
            },

            openCreateModal() {
                this.editingCategory = null;
                this.images = [];
                this.form = { name: '', description: '', is_active: true, show_on_home: true, sort_order: 0 };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            openEditModal(category, categoryImages = []) {
                this.editingCategory = category;
                this.images = Array.isArray(categoryImages) ? categoryImages.map(i => ({
                    id: i.id,
                    path: i.path,
                    is_primary: !!i.is_primary,
                })) : [];
                this.form = {
                    name: category.name,
                    description: category.description || '',
                    is_active: category.is_active,
                    show_on_home: category.show_on_home ?? true,
                    sort_order: category.sort_order || 0,
                };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            closeModal() {
                this.showModal = false;
                this.editingCategory = null;
                this.images = [];
            },

            async uploadImages() {
                if (!this.$refs.imageInput.files.length) return;
                this.uploading = true;
                try {
                    const files = this.$refs.imageInput.files;
                    for (const file of files) {
                        const fd = new FormData();
                        fd.append('image', file);
                        const res = await fetch(`/admin/categories/${this.editingCategory.id}/images`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: fd,
                        });
                        const data = await res.json();
                        if (data.success && data.image) {
                            this.images.push({ id: data.image.id, path: data.image.path, is_primary: !!data.image.is_primary });
                        }
                    }
                    this.$refs.imageInput.value = '';
                } catch (e) {
                    alert('Upload failed. Please try again.');
                }
                this.uploading = false;
            },

            async deleteImage(id) {
                if (!confirm('Delete this image?')) return;
                try {
                    const res = await fetch(`/admin/categories/images/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.images = this.images.filter(i => i.id !== id);
                    }
                } catch (e) {
                    alert('Delete failed. Please try again.');
                }
            },

            async setPrimary(id) {
                try {
                    const res = await fetch(`/admin/categories/images/${id}/primary`, {
                        method: 'PUT',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.images.forEach(i => i.is_primary = (i.id === id));
                    }
                } catch (e) {
                    alert('Update failed. Please try again.');
                }
            },

            async saveCategory() {
                this.saving = true;
                try {
                    const url = this.editingCategory
                        ? `/admin/categories/${this.editingCategory.id}`
                        : '/admin/categories';
                    const method = this.editingCategory ? 'PUT' : 'POST';

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

            deleteCategory(id) {
                this.deleteId = id;
                this.showDeleteConfirm = true;
                this.$nextTick(() => lucide.createIcons());
            },

            async confirmDelete() {
                try {
                    const response = await fetch(`/admin/categories/${this.deleteId}`, {
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
                    this.selectedIds = @js($categories->pluck('id')->toArray());
                    this.allSelected = true;
                } else {
                    this.selectedIds = [];
                    this.allSelected = false;
                }
            },

            async bulkAction(action) {
                const labels = { delete: 'Delete', activate: 'Activate', deactivate: 'Deactivate' };
                const msg = action === 'delete'
                    ? 'Delete selected categories? Categories with items will be skipped.'
                    : labels[action] + ' selected categories?';
                if (!confirm(msg)) return;
                try {
                    const res = await fetch('{{ route("admin.categories.bulk") }}', {
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
