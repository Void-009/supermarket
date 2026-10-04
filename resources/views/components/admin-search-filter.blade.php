@if(request('search'))
@php
    $searchTerm = request('search');
    $queryParams = request()->except(['search', 'page']);
    $clearUrl = url()->current() . (count($queryParams) ? '?' . http_build_query($queryParams) : '');
@endphp
<div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div class="flex items-center gap-2 text-sm text-emerald-800 min-w-0">
        <i data-lucide="search" class="w-4 h-4 shrink-0"></i>
        <span class="min-w-0 break-words">Searching for "<strong>{{ $searchTerm }}</strong>"</span>
    </div>
    <a href="{{ $clearUrl }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 underline shrink-0">Clear search</a>
</div>
@endif
