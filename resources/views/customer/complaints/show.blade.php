@extends('layouts.main')
@section('title', 'Complaint')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="complaintView()" x-init="init()">

    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('customer.orders.show', $complaint->order_id) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Order #{{ $complaint->order_id }}
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        @include('components.alert', ['type' => 'success', 'message' => session('success')])
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Complaint Info --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">Complaint Details</h3>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Order</label>
                        <a href="{{ route('customer.orders.show', $complaint->order_id) }}" class="block text-sm font-semibold text-emerald-600 hover:text-emerald-700 mt-1">
                            Order #{{ $complaint->order_id }}
                        </a>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</label>
                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $complaint->subject }}</div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                        <div class="mt-1">
                            @if($complaint->status === 'open')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">Open</span>
                            @elseif($complaint->status === 'in_progress')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">In Progress</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Resolved</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date</label>
                        <div class="text-sm text-gray-700 mt-1">{{ $complaint->created_at->format('M d, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conversation Thread --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">Conversation</h3>

                {{-- Original Message --}}
                <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex items-center gap-3 mb-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <span class="text-sm font-semibold text-gray-900 truncate block">{{ Auth::user()->name }}</span>
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
                            <div class="flex items-center gap-3 mb-3 min-w-0">
                                <div class="w-8 h-8 rounded-full {{ $isAdmin ? 'bg-emerald-600' : 'bg-gray-500' }} flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                                    {{ substr($reply->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm font-semibold text-gray-900 truncate block">{{ $reply->user->name ?? 'User' }}</span>
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
                @if($complaint->status !== 'resolved')
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
                @else
                <div class="border-t border-gray-100 pt-6 text-center">
                    <p class="text-sm text-gray-500">This complaint has been resolved.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function complaintView() {
    return {
        replyMessage: '',
        sending: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        async sendReply() {
            if (!this.replyMessage.trim() || this.sending) return;
            this.sending = true;
            try {
                const res = await fetch('{{ route("customer.complaints.reply", $complaint->id) }}', {
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
