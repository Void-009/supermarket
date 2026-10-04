@props(['containerId', 'title' => null])

{{-- Fixed overlay so opening the camera never pushes or covers the page layout. --}}
<div x-show="scannerActive" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true"
     @keydown.escape.window="stopScanner()">
    <div class="absolute inset-0 bg-slate-900/70" @click="stopScanner()"></div>

    <div class="relative flex min-h-full items-start justify-center px-4 py-10 sm:py-16">
        <div class="w-full max-w-[22rem]">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-white">{{ $title ?? __('messages.barcode_scanner') }}</h3>
                <button type="button" @click="stopScanner()"
                    class="rounded-lg p-1.5 text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                    :title="'{{ __('messages.stop_scanner') }}'"
                    :aria-label="'{{ __('messages.stop_scanner') }}'">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div id="{{ $containerId }}" class="h-52 w-full overflow-hidden rounded-xl border-2 border-emerald-400 bg-black sm:h-64"></div>

            <p class="mt-2 text-center text-xs text-white/70">{{ __('messages.point_camera_barcode') }}</p>
        </div>
    </div>
</div>
