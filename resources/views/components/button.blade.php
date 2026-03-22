@props([
    'variant' => 'primary',  // primary | secondary | danger | warning | success
    'size'    => 'md',        // sm | md | lg
    'type'    => 'button',
    'href'    => null,
    'disabled' => false,
])

@php
$base = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs gap-1.5',
    'md' => 'px-4 py-2 text-sm gap-2',
    'lg' => 'px-5 py-2.5 text-base gap-2',
];

$variants = [
    'primary'   => 'bg-navy text-white hover:bg-navy-dark focus-visible:ring-navy dark:bg-navy-light dark:hover:bg-navy',
    'secondary' => 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus-visible:ring-gray-400',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500',
    'warning'   => 'bg-amber-500 text-white hover:bg-amber-600 focus-visible:ring-amber-400',
    'success'   => 'bg-green-600 text-white hover:bg-green-700 focus-visible:ring-green-500',
];

$classes = $base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
