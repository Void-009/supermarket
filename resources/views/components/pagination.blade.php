@if ($paginator->hasPages())
<nav class="flex items-center justify-center gap-1.5 sm:gap-2 mt-8 flex-wrap">
    @if ($paginator->onFirstPage())
        <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-300 bg-gray-50 rounded-lg cursor-not-allowed">&laquo; {{ __('messages.prev') }}</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors">&laquo; {{ __('messages.prev') }}</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-400">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-white bg-emerald-500 rounded-lg font-medium">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors">{{ __('messages.next') }} &raquo;</a>
    @else
        <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-300 bg-gray-50 rounded-lg cursor-not-allowed">{{ __('messages.next') }} &raquo;</span>
    @endif
</nav>
@endif
