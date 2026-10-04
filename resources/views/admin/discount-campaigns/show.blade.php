@extends('layouts.admin')

@section('title', $discountCampaign->name)
@section('header', $discountCampaign->name)

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center gap-3 flex-wrap">
        <a href="{{ route('admin.discount-campaigns.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 bg-white border border-gray-200 rounded-xl transition-colors">
            &larr; Back to Campaigns
        </a>
        <a href="{{ route('admin.discount-campaigns.edit', $discountCampaign) }}" class="px-4 py-2 text-sm text-white bg-blue-500 hover:bg-blue-600 rounded-xl transition-colors">
            Edit Campaign
        </a>
        <form method="POST" action="{{ route('admin.discount-campaigns.toggle-active', $discountCampaign) }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ $discountCampaign->isCurrentlyActive() ? 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                {{ $discountCampaign->isCurrentlyActive() ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Campaign Details --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Campaign Details</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Discount</dt>
                        <dd class="font-semibold text-emerald-600">{{ $discountCampaign->discount_percentage }}%</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>
                            @if($discountCampaign->isCurrentlyActive())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-medium">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                                </span>
                            @elseif($discountCampaign->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 rounded-lg text-xs font-medium">
                                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Scheduled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 text-gray-500 rounded-lg text-xs font-medium">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactive
                                </span>
                            @endif
                        </dd>
                    </div>
                    @if($discountCampaign->starts_at)
                    <div>
                        <dt class="text-gray-500">Start Date</dt>
                        <dd class="font-medium">{{ $discountCampaign->starts_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @endif
                    @if($discountCampaign->ends_at)
                    <div>
                        <dt class="text-gray-500">End Date</dt>
                        <dd class="font-medium">{{ $discountCampaign->ends_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Created by</dt>
                        <dd class="font-medium">{{ $discountCampaign->creator?->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Last Notified</dt>
                        <dd class="font-medium">{{ $discountCampaign->notified_at ? $discountCampaign->notified_at->diffForHumans() : 'Never' }}</dd>
                    </div>
                </dl>
                @if($discountCampaign->description)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Description</dt>
                    <dd class="text-sm text-gray-700">{{ $discountCampaign->description }}</dd>
                </div>
                @endif
            </div>

            {{-- Included Items --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Included Items ({{ $discountCampaign->items->count() }})</h3>
                @if($discountCampaign->items->isEmpty())
                    <p class="text-sm text-gray-400">No items in this campaign.</p>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($discountCampaign->items as $item)
                        <div class="flex items-center gap-3 py-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 shrink-0 flex items-center justify-center overflow-hidden">
                                @if($item->image_url)
                                    <img src="{{ asset('storage/' . $item->image_url) }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <span class="text-lg">🛒</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $item->display_name }}</p>
                                <p class="text-xs text-gray-500">
                                    ₦{{ number_format($item->selling_price, 2) }}
                                    <span class="text-emerald-600 font-medium"> → ₦{{ number_format($item->selling_price * (1 - $discountCampaign->discount_percentage / 100), 2) }}</span>
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Notify Panel --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 sticky top-24">
                <h3 class="font-semibold text-gray-900 mb-3">Notify Customers</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Send personalized push notifications about this campaign to customers who have favorited or purchased these items.
                </p>

                <button id="notifyBtn" type="button"
                    class="w-full px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors inline-flex items-center justify-center gap-2"
                    {{ !$discountCampaign->isCurrentlyActive() ? 'disabled' : '' }}>
                    <svg id="notifySpinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="notifyText">Notify Interested Customers</span>
                </button>

                @if(!$discountCampaign->isCurrentlyActive())
                    <p class="text-xs text-amber-600 mt-2">Activate the campaign to enable notifications.</p>
                @endif

                <div id="notifyResult" class="mt-3 hidden"></div>

                <hr class="my-4 border-gray-100">

                <div class="text-xs text-gray-400 space-y-1">
                    <p>💡 Notifications are personalized based on each customer's:</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>Favorited items</li>
                        <li>Purchase history</li>
                        <li>Favorite categories</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('notifyBtn')?.addEventListener('click', async function() {
        var btn = this;
        var spinner = document.getElementById('notifySpinner');
        var text = document.getElementById('notifyText');
        var result = document.getElementById('notifyResult');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        text.textContent = 'Sending personalized notifications...';
        result.classList.add('hidden');

        try {
            var response = await fetch('{{ route("admin.discount-campaigns.notify", $discountCampaign) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            var data = await response.json();

            result.classList.remove('hidden');
            if (data.success) {
                result.className = 'mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800';
                result.textContent = data.message;
            } else {
                result.className = 'mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800';
                result.textContent = data.message || 'Failed to send notifications.';
            }
        } catch (e) {
            result.classList.remove('hidden');
            result.className = 'mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800';
            result.textContent = 'Network error. Please try again.';
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            text.textContent = 'Notify Interested Customers';
        }
    });
</script>
@endpush
@endsection
