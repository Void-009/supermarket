@props(['size' => '6'])

<div class="flex items-center justify-center" wire:loading>
    <div class="animate-spin rounded-full h-{{ $size }} w-{{ $size }} border-b-2 border-emerald-500"></div>
</div>
