@props([
    'icon'    => null,   // svg path string or null
    'message' => null,
])

<div class="text-center py-12 text-gray-400 dark:text-gray-500">
    @if($icon)
        <svg class="mx-auto h-12 w-12 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            {!! $icon !!}
        </svg>
    @endif
    <p class="text-sm">{{ $message ?? $slot }}</p>
</div>
