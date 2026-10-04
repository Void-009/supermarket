@extends('layouts.admin')

@section('title', 'Shop Slideshow')

@section('content')
<div class="p-4 sm:p-6" x-data="slideshowManager()" x-init="init()">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Shop Slideshow Images</h1>
            <p class="text-sm text-gray-500 mt-1">Manage background images for the shop page header slideshow.</p>
        </div>
    </div>

    <x-alert />

    {{-- Upload --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <input type="file" accept="image/*" x-ref="fileInput" class="block w-full min-w-0 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
            <button type="button" @click="upload()" :disabled="uploading" class="shrink-0 px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors disabled:opacity-50">
                <span x-show="!uploading">Upload</span>
                <span x-show="uploading" x-cloak>Uploading...</span>
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-2">Recommended size: 1920x600px. Max 10MB. JPEG, PNG, WebP.</p>
    </div>

    {{-- Image Grid --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <div x-show="!images.length" class="text-center py-12">
            <i data-lucide="image" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-gray-500 text-sm">No slideshow images yet. Upload some to display on the shop page.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" x-show="images.length" x-cloak>
            <template x-for="(img, idx) in images" :key="img.id">
                <div class="relative group rounded-xl overflow-hidden border border-gray-200 bg-gray-50 aspect-video">
                    <img :src="'/storage/' + img.path" class="w-full h-full object-cover" loading="lazy">
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                        <button type="button" @click="toggle(img.id)" :class="img.is_active ? 'bg-emerald-500' : 'bg-gray-400'" class="text-xs px-3 py-1.5 text-white rounded-lg font-semibold transition-colors" x-text="img.is_active ? 'Active' : 'Inactive'"></button>
                        <button type="button" @click="remove(img.id, idx)" class="text-xs px-3 py-1.5 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">Delete</button>
                    </div>
                    <div class="absolute bottom-1.5 left-1.5 text-[10px] bg-black/60 text-white px-2 py-0.5 rounded" x-text="'#' + (idx + 1)"></div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function slideshowManager() {
    return {
        images: [],
        uploading: false,

        async init() {
            const res = await fetch('{{ route("admin.slideshow-images.list") }}');
            const data = await res.json();
            this.images = data.images || [];
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        async upload() {
            const input = this.$refs.fileInput;
            if (!input.files.length) return;
            this.uploading = true;
            const fd = new FormData();
            fd.append('image', input.files[0]);
            try {
                const res = await fetch('{{ route("admin.slideshow-images.upload") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: fd,
                });
                const data = await res.json();
                if (data.success) {
                    this.images.push(data.image);
                    input.value = '';
                }
            } catch (e) {}
            this.uploading = false;
        },

        async toggle(id) {
            const res = await fetch('{{ route("admin.slideshow-images.toggle", "__ID__") }}'.replace('__ID__', id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                const img = this.images.find(i => i.id === id);
                if (img) img.is_active = !img.is_active;
            }
        },

        async remove(id, idx) {
            if (!confirm('Delete this image?')) return;
            const res = await fetch('{{ route("admin.slideshow-images.destroy", "__ID__") }}'.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) this.images.splice(idx, 1);
        },
    };
}
</script>
@endpush
