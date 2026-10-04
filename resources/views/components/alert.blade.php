@props(['type' => 'success', 'message' => ''])

@if($message)
<div class="rounded-lg p-4 mb-4 {{ $type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' }}">
    <div class="flex items-start gap-2">
        <span class="min-w-0 break-words">{{ $message }}</span>
    </div>
</div>
@endif
