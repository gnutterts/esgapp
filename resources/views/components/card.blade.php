@props([
    'title'     => null,
    'padded'    => true,
    'flush'     => false,  // remove overflow-hidden (for tables etc that manage their own)
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm ' . ($flush ? '' : 'overflow-hidden')]) }}>
    @if($title)
        <div class="bg-navy dark:bg-navy-dark px-4 py-3 flex items-center justify-between">
            <h2 class="text-white font-semibold text-sm uppercase tracking-wide">{{ $title }}</h2>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $padded ? 'p-4 sm:p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
