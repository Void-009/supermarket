@extends('layouts.admin')

@section('title', 'Complaint #' . $complaint->id)
@section('header', 'Complaint Details')

@section('content')
<div x-data="complaintDetail()" x-init="init()">

    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('admin.complaints.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Complaints
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Complaint Info --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">Complaint Info</h3>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Order</label>
                        <a href="{{ route('admin.orders.show', $complaint->order_id) }}" class="block text-sm font-semibold text-emerald-600 hover:text-emerald-700 mt-1">
                            Order #{{ $complaint->order_id }}
                        </a>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</label>
                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $complaint->user->name ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-400 break-all">{{ $complaint->user->email ?? '' }}</div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</label>
                        <div class="text-sm font-medium text-gray-900 mt-1 break-words">{{ $complaint->subject }}</div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date</label>
                        <div class="text-sm text-gray-700 mt-1">{{ $complaint->created_at->format('M d, Y \a\t g:i A') }}</div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                        <div class="mt-2">
                            <select x-model="currentStatus" @change="updateStatus()" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Complaint Thread --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">Conversation</h3>

                {{-- Original Message --}}
                <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold uppercase">
                            {{ substr($complaint->user->name ?? 'C', 0, 1) }}
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-900">{{ $complaint->user->name ?? 'Customer' }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $complaint->created_at->format('M d, g:i A') }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-line break-words">{{ $complaint->message }}</p>
                </div>

                {{-- Replies --}}
                <div class="space-y-4 mb-6">
                    @foreach($complaint->replies as $reply)
                        @php
                            $isAdmin = in_array($reply->user->role ?? '', ['superadmin', 'admin', 'agent']);
                        @endphp
                        <div class="p-4 rounded-xl border {{ $isAdmin ? 'bg-emerald-50 border-emerald-100' : 'bg-gray-50 border-gray-100' }}">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <div class="w-8 h-8 rounded-full {{ $isAdmin ? 'bg-emerald-600' : 'bg-gray-500' }} flex items-center justify-center text-white text-xs font-bold uppercase">
                                    {{ substr($reply->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $reply->user->name ?? 'User' }}</span>
                                    @if($isAdmin)
                                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Staff</span>
                                    @endif
                                    <span class="text-xs text-gray-400 ml-2">{{ $reply->created_at->format('M d, g:i A') }}</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-line break-words">{{ $reply->message }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Reply Form --}}
                <div class="border-t border-gray-100 pt-6">
                    <form @submit.prevent="sendReply()">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Reply</label>
                        <textarea x-model="replyMessage" rows="4" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none" placeholder="Type your reply..."></textarea>
                        <div class="flex justify-end mt-3">
                            <button type="submit" :disabled="!replyMessage.trim() || sending" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                                <span x-show="!sending">Send Reply</span>
                                <span x-show="sending" x-cloak>Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Toast --}}
    <div x-show="showSuccess" x-cloak x-transition class="fixed top-4 right-4 left-4 z-50 sm:top-6 sm:right-6 sm:left-auto sm:max-w-sm sm:w-full bg-green-50 border border-green-200 rounded-xl shadow-lg p-4">
        <div class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5 shrink-0"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-green-800" x-text="successMessage"></p>
            </div>
            <button @click="showSuccess = false" class="text-green-400 hover:text-green-600 shrink-0">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<script>
    function complaintDetail() {
        return {
            currentStatus: '{{ $complaint->status }}',
            replyMessage: '',
            sending: false,
            showSuccess: false,
            successMessage: '',
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

            init() {
                this.$nextTick(() => lucide.createIcons());
            },

            async updateStatus() {
                try {
                    const res = await fetch('{{ route("admin.complaints.update-status", $complaint->id) }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: this.currentStatus }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.successMessage = 'Status updated.';
                        this.showSuccess = true;
                        setTimeout(() => this.showSuccess = false, 3000);
                    }
                } catch (e) {
                    alert('Failed to update status.');
                }
            },

            async sendReply() {
                if (!this.replyMessage.trim() || this.sending) return;
                this.sending = true;
                try {
                    const res = await fetch('{{ route("admin.complaints.reply", $complaint->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message: this.replyMessage }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.reload();
                    }
                } catch (e) {
                    alert('Failed to send reply.');
                }
                this.sending = false;
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
