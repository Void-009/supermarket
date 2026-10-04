@extends('layouts.admin')
@section('title', 'Delivery Settings')
@section('header', 'Delivery Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-6 leading-snug">Delivery Fee & Customer Contact Settings</h3>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.delivery-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="hidden" name="show_delivery_fee_selector" value="0">
                            <input type="checkbox" name="show_delivery_fee_selector" value="1" {{ $settings['show_delivery_fee_selector'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-sm font-medium text-gray-700">Show delivery fee selector to customers</span>
                            <p class="text-xs text-gray-400 mt-0.5">When off, customers will see "Contact Agent" instead of the delivery area dropdown and fee</p>
                        </div>
                    </label>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">Contact Agent Settings</h4>
                    <p class="text-xs text-gray-500 mb-4">Configure the contact information shown to customers when the delivery fee selector is disabled, or for bulk orders.</p>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Message</label>
                            <textarea name="contact_agent_message" rows="3" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="Contact our delivery agent to confirm the delivery fee...">{{ old('contact_agent_message', $settings['contact_agent_message']) }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">This message is shown to customers on the checkout page.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Numbers</label>
                                <div x-data="numberList(@json($settings['contact_agent_phones']))">
                                    <div class="space-y-2">
                                        <template x-for="(val, i) in values" :key="i">
                                            <div class="flex gap-2">
                                                <input type="text" name="contact_agent_phones[]" x-model="values[i]" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. 08012345678">
                                                <button type="button" @click="remove(i)" class="shrink-0 w-10 rounded-xl border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 transition-colors flex items-center justify-center" title="Remove number">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="add()" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add another number
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Used for call/SMS. Customers see the popup list when more than one is set.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">WhatsApp Numbers</label>
                                <div x-data="numberList(@json($settings['contact_agent_whatsapps']))">
                                    <div class="space-y-2">
                                        <template x-for="(val, i) in values" :key="i">
                                            <div class="flex gap-2">
                                                <input type="text" name="contact_agent_whatsapps[]" x-model="values[i]" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. 2348061400220">
                                                <button type="button" @click="remove(i)" class="shrink-0 w-10 rounded-xl border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 transition-colors flex items-center justify-center" title="Remove number">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="add()" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add another number
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Include country code. Falls back to Payment Settings WhatsApp if empty.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">Bulk Order Settings</h4>
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Bulk Quantity Threshold</label>
                        <input type="number" name="bulk_quantity_threshold" value="{{ old('bulk_quantity_threshold', $settings['bulk_quantity_threshold']) }}" min="1" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="10">
                        <p class="text-xs text-gray-400 mt-1">Orders with total quantity above this threshold (or containing bulky items) will be treated as bulk orders.</p>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors shadow-sm">
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function numberList(initial) {
        return {
            values: (initial && initial.length ? initial.map(String) : ['']),
            add() { this.values.push(''); },
            remove(i) { this.values.splice(i, 1); if (this.values.length === 0) this.values.push(''); },
        };
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endpush
