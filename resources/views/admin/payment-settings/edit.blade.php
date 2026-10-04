@extends('layouts.admin')
@section('title', 'Payment Settings')
@section('header', 'Payment Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-6 leading-snug">Bank Account Details & Delivery Fee</h3>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.payment-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $paymentSettings->bank_name ?? 'Access Bank') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" name="account_name" value="{{ old('account_name', $paymentSettings->account_name ?? 'M-Delight Singer Stores') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number', $paymentSettings->account_number ?? '1234567890') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Code (Optional)</label>
                    <input type="text" name="bank_code" value="{{ old('bank_code', $paymentSettings->bank_code ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Fee</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₦</span>
                        <input type="number" name="delivery_fee" value="{{ old('delivery_fee', $paymentSettings->delivery_fee ?? '1500') }}" step="0.01" min="0" class="w-full pl-8 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Transfer Instructions</label>
                    <textarea name="bank_transfer_instructions" rows="3" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="e.g., Transfer to the above account and email the receipt to support@m-delight.com">{{ old('bank_transfer_instructions', $paymentSettings->bank_transfer_instructions ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp Number <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $paymentSettings->whatsapp_number ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. 2348061400220">
                    <p class="text-xs text-gray-500 mt-1">Include country code without +. Customers will click the WhatsApp button to chat this number.</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">Social Media Links</h4>
                    <p class="text-xs text-gray-500 mb-4">Used on the store footer. Leave blank to hide the icon.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Facebook URL</label>
                            <input type="url" name="facebook_url" value="{{ old('facebook_url', $paymentSettings->facebook_url ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="https://facebook.com/yourpage">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instagram URL</label>
                            <input type="url" name="instagram_url" value="{{ old('instagram_url', $paymentSettings->instagram_url ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="https://instagram.com/yourpage">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">X (Twitter) URL</label>
                            <input type="url" name="x_url" value="{{ old('x_url', $paymentSettings->x_url ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="https://x.com/yourhandle">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TikTok URL</label>
                            <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $paymentSettings->tiktok_url ?? '') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="https://tiktok.com/@yourhandle">
                        </div>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $paymentSettings->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                    <label class="ml-2 text-sm font-medium text-gray-700">Active</label>
                </div>

                <div class="flex justify-end pt-6">
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
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endpush