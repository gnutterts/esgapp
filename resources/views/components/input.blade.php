@props([
    'label'    => null,
    'name'     => null,
    'type'     => 'text',
    'error'    => null,
    'helpText' => null,
])

@php
$inputClass = 'w-full border rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors focus:outline-none focus:ring-2 focus:ring-navy focus:border-transparent dark:focus:ring-blue-400 '
    . ($error ? 'border-red-400 bg-red-50 dark:bg-red-950/30' : 'border-gray-300 dark:border-gray-600');
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
        </label>
    @endif
    <input
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $inputClass]) }}
    >
    @if($helpText)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
    @endif
    @if($error)
        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $error }}</p>
    @elseif($name && $errors->has($name))
        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
