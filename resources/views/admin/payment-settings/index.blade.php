@extends('layouts.admin')
@section('title', 'Payment Settings')
@section('header', 'Payment Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Current Settings</h3>
            <a href="{{ route('admin.payment-settings.edit') }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                {{ $paymentSettings ? 'Edit Settings' : 'Set Up Now' }}
            </a>
        </div>

        @if(!$paymentSettings)
            <div class="p-8 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i data-lucide="settings" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">No Payment Settings Configured</h4>
                <p class="text-sm text-gray-500 mb-4">Set up your bank account details, delivery fee, and WhatsApp number for customers.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Bank Name</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $paymentSettings->bank_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Account Name</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $paymentSettings->account_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Account Number</p>
                    <p class="text-sm font-semibold text-gray-900 font-mono break-all">{{ $paymentSettings->account_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Delivery Fee</p>
                    <p class="text-sm font-semibold text-gray-900">₦{{ number_format($paymentSettings->delivery_fee, 2) }}</p>
                </div>
                @if($paymentSettings->bank_code)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Bank Code</p>
                        <p class="text-sm font-mono text-gray-900 break-all">{{ $paymentSettings->bank_code }}</p>
                    </div>
                @endif
                <div class="md:col-span-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $paymentSettings->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }} border">
                        {{ $paymentSettings->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($paymentSettings->bank_transfer_instructions)
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Transfer Instructions</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $paymentSettings->bank_transfer_instructions }}</p>
                    </div>
                @endif
                @if($paymentSettings->whatsapp_number)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">WhatsApp Number</p>
                        <p class="text-sm font-semibold text-gray-900 break-all">{{ $paymentSettings->whatsapp_number }}</p>
                    </div>
                @endif
                <div class="md:col-span-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Social Links</p>
                    @php
                        $socials = [
                            'Facebook' => $paymentSettings->facebook_url,
                            'Instagram' => $paymentSettings->instagram_url,
                            'X (Twitter)' => $paymentSettings->x_url,
                            'TikTok' => $paymentSettings->tiktok_url,
                        ];
                    @endphp
                    @if(array_filter($socials))
                        <ul class="space-y-1">
                            @foreach($socials as $name => $url)
                                @if($url)
                                    <li class="text-sm text-gray-700"><span class="font-medium text-gray-900">{{ $name }}:</span>
                                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline break-all">{{ $url }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Not set</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
