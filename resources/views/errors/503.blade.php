@extends('layouts.' . (auth()->check() && auth()->user()->isAdminOrAgent() ? 'admin' : 'main'))

@section('title', '503 - Maintenance')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-6xl sm:text-8xl font-black text-slate-400 mb-4" style="font-family:'Poppins',sans-serif;">503</div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">Under Maintenance</h1>
        <p class="text-gray-500 mb-8 break-words">We are currently performing maintenance. Please check back shortly.</p>
        <a href="{{ route('home') }}" class="inline-flex w-full sm:w-auto items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Try Again
        </a>
    </div>
</div>
@endsection
