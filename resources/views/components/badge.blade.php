@props([
    'color' => 'gray',  // gray | blue | green | yellow | red | purple | amber
])

@php
$colors = [
    'gray'   => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200',
    'blue'   => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200',
    'green'  => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200',
    'yellow' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-200',
    'red'    => 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200',
    'purple' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200',
    'amber'  => 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
