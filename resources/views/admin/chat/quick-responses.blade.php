@php
    $title = 'Quick Responses';
@endphp
@extends('layouts.admin')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Quick Responses</h1>
    </div>
@endsection

@section('content')
<div x-data="quickResponsesManager()" x-init="init()">
    {{-- Add Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add New Quick Response</h2>
        <form @submit.prevent="save()" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                <input type="text" x-model="form.title" placeholder="e.g. Ask Price"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" required>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
                <div class="flex gap-2">
                    <input type="text" x-model="form.message" placeholder="The message to send..."
                        class="flex-1 min-w-0 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" required>
                    <button type="submit" :disabled="!form.title.trim() || !form.message.trim()"
                        class="shrink-0 px-6 py-2.5 bg-emerald-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50">
                        Add
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Responses List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <template x-if="responses.length === 0">
            <div class="text-center py-12 text-gray-400 text-sm">No quick responses yet. Add one above.</div>
        </template>
        <template x-for="(r, i) in responses" :key="r.id">
            <div class="flex items-center gap-3 px-4 sm:px-6 py-4 border-b border-gray-100 last:border-0">
                <span class="text-xs text-gray-400 w-6 shrink-0" x-text="i + 1"></span>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-gray-900 truncate" x-text="r.title"></div>
                    <div class="text-xs text-gray-500 truncate mt-0.5" x-text="r.message"></div>
                </div>
                <button @click="remove(r.id)" class="shrink-0 text-red-400 hover:text-red-600 transition-colors text-sm whitespace-nowrap">Delete</button>
            </div>
        </template>
    </div>
</div>

<script>
function quickResponsesManager() {
    return {
        responses: [],
        form: { title: '', message: '' },
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        init() {
            this.fetch();
        },

        fetch() {
            fetch('/admin/chat/quick-responses/data')
                .then(r => r.json())
                .then(data => { this.responses = data.responses || []; });
        },

        save() {
            if (!this.form.title.trim() || !this.form.message.trim()) return;
            fetch('/admin/chat/quick-responses', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(this.form),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.responses.push(data.response);
                    this.form = { title: '', message: '' };
                }
            });
        },

        remove(id) {
            if (!confirm('Delete this quick response?')) return;
            fetch(`/admin/chat/quick-responses/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) this.responses = this.responses.filter(r => r.id !== id);
            });
        }
    }
}
</script>
@endsection
