@extends('layouts.main')
@section('title', 'Order #' . $order->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="orderDetail()" x-init="init()">

    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('customer.orders') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('messages.back_to_my_orders') }}
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        @include('components.alert', ['type' => 'success', 'message' => session('success')])
    @endif

    {{-- Order Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.order') }} #{{ $order->id }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ __('messages.placed_on', ['date' => $order->created_at->format('M d, Y \a\t g:i A')]) }}</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                @if($order->status === 'cancelled')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-red-50 text-red-700 border border-red-200">{{ __('messages.status_cancelled') }}</span>
                @endif
                @if($order->isEditableByCustomer())
                    <a href="{{ route('customer.orders.edit', $order) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 rounded-xl text-sm font-semibold text-white transition-colors shadow-sm">
                        <i data-lucide="pencil" class="w-4 h-4"></i> {{ __('messages.edit_order') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Explain the lock rather than hiding the button, so the customer
             knows the order can no longer be changed and why. --}}
        @if(! $order->isEditableByCustomer())
            <div class="mt-4 flex items-start gap-2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                <i data-lucide="lock" class="w-4 h-4 mt-0.5 shrink-0 text-gray-400"></i>
                <span>{{ $order->customerEditLockReason() }}</span>
            </div>
        @endif
    </div>

    {{-- Status Timeline --}}
    @if(!$isCancelled)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_status') }}</h2>
        <div class="relative overflow-x-auto">
            <div class="min-w-[480px] relative">
                <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200"></div>
                <div class="absolute top-4 left-0 h-0.5 bg-emerald-500 transition-all duration-500" style="width: {{ $currentIndex !== false ? ($currentIndex / (count($statusSteps) - 1) * 100) : 0 }}%"></div>
                <div class="relative flex justify-between">
                    @foreach($statusSteps as $index => $step)
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold z-10 {{ $index <= $currentIndex ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                                @if($index < $currentIndex)
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="text-xs font-medium text-gray-600 mt-2 capitalize">{{ __("messages.status_{$step}") }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Order Items --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">{{ __('messages.items_ordered') }}</h2>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl">
                        <div class="shrink-0 w-14 h-14 bg-white rounded-lg overflow-hidden border border-gray-100">
                            @if($item->item->image_url)
                                <img src="{{ asset('storage/' . $item->item->image_url) }}" alt="{{ $item->item->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                    <i data-lucide="image" class="w-5 h-5 text-emerald-300"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ $item->item->name }}</p>
                            <p class="text-xs text-gray-400">{{ __('messages.qty') }}: {{ $item->quantity }} × ₦{{ number_format($item->price_at_order, 2) }}</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 shrink-0">₦{{ number_format($item->price_at_order * $item->quantity, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">{{ __('messages.summary') }}</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('messages.subtotal') }}</span>
                        <span class="font-medium text-gray-900">₦{{ number_format($order->total_amount - $order->delivery_fee, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 min-w-0 break-words">{{ __('messages.delivery') }}{{ $order->deliveryLocation ? ' (' . $order->deliveryLocation->name . ')' : '' }}</span>
                        <span class="font-medium {{ $order->delivery_fee > 0 ? 'text-gray-900' : 'text-green-600' }} shrink-0 ml-2">
                            {{ $order->delivery_fee > 0 ? '₦' . number_format($order->delivery_fee, 2) : __('messages.free') }}
                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <span class="font-bold text-gray-900">{{ __('messages.total') }}</span>
                        <span class="text-lg font-extrabold text-emerald-600">₦{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('messages.payment_method') }}</span>
                        <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('messages.payment_status') }}</span>
                        @if($order->payment_status === 'verified')
                            <span class="font-medium text-green-600">{{ __('messages.payment_verified') }}</span>
                        @elseif($order->payment_status === 'failed')
                            <span class="font-medium text-red-600">{{ __('messages.payment_failed') }}</span>
                        @elseif($order->payment_status === 'refunded')
                            <span class="font-medium text-purple-600">{{ __('messages.payment_refunded') }}</span>
                        @else
                            <span class="font-medium text-yellow-600">{{ __('messages.payment_pending') }}</span>
                        @endif
                    </div>
                    @if($order->delivery_address)
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('messages.address') }}</span>
                        <span class="font-medium text-gray-900 text-right max-w-[200px] break-words">{{ $order->delivery_address }}</span>
                    </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4">
                    <button type="button" onclick="openOrderReceipt({{ $order->id }})" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-100 rounded-xl transition-colors">
                        <i data-lucide="receipt" class="w-4 h-4"></i> {{ __('messages.view_receipt') }}
                    </button>
                </div>

                {{-- Log Complaint Button --}}
                @if(!in_array($order->status, ['cancelled']) && $order->complaints->isEmpty())
                <div class="border-t border-gray-100 mt-4 pt-4">
                    <button @click="showComplaintModal = true" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-colors">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> {{ __('messages.log_complaint') }}
                    </button>
                </div>
                @endif
            </div>

            {{-- Existing Complaints --}}
            @if($order->complaints->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-4">
                <h3 class="text-sm font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">{{ __('messages.complaints') }}</h3>
                <div class="space-y-2">
                    @foreach($order->complaints as $complaint)
                    <a href="{{ route('customer.complaints.show', $complaint->id) }}" class="block p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 line-clamp-1">{{ $complaint->subject }}</span>
                            @if($complaint->status === 'open')
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">{{ __('messages.complaint_open') }}</span>
                            @elseif($complaint->status === 'in_progress')
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700">{{ __('messages.complaint_in_progress') }}</span>
                            @else
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">{{ __('messages.complaint_resolved') }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $complaint->created_at->format('M d, Y') }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Complaint Modal --}}
    <div x-show="showComplaintModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showComplaintModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-4 sm:p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.log_complaint') }}</h3>
                <button @click="showComplaintModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form @submit.prevent="submitComplaint()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.subject') }} <span class="text-red-500">*</span></label>
                        <input x-model="complaintForm.subject" type="text" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="{{ __('messages.subject_placeholder') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.message') }} <span class="text-red-500">*</span></label>
                        <textarea x-model="complaintForm.message" rows="5" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="{{ __('messages.message_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" @click="showComplaintModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ __('messages.cancel') }}</button>
                    <button type="submit" :disabled="submittingComplaint" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-show="!submittingComplaint">{{ __('messages.submit_complaint') }}</span>
                        <span x-show="submittingComplaint" x-cloak>{{ __('messages.submitting') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function orderDetail() {
    return {
        showComplaintModal: false,
        submittingComplaint: false,
        complaintForm: {
            subject: '',
            message: '',
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        async submitComplaint() {
            if (this.submittingComplaint) return;
            this.submittingComplaint = true;
            try {
                const res = await fetch('{{ route("customer.complaints.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: {{ $order->id }},
                        subject: this.complaintForm.subject,
                        message: this.complaintForm.message,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to submit complaint.');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            }
            this.submittingComplaint = false;
        },
    };
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

{{-- Hidden receipt template --}}
<div id="receipt-{{ $order->id }}" class="hidden">
    <x-receipt-content :order="$order" />
</div>

{{-- Receipt popup --}}
<x-receipt-modal />
@endsection
