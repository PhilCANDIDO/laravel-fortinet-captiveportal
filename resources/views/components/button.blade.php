@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, danger, success, warning
    'size' => 'md' // sm, md, lg
])

@php
$variants = [
    'primary' => 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300',
    'secondary' => 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100',
    'danger' => 'text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300',
    'success' => 'text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300',
    'warning' => 'text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300',
];

$sizes = [
    'sm' => 'px-3 py-2 text-xs',
    'md' => 'px-5 py-2.5 text-sm',
    'lg' => 'px-5 py-3 text-base',
];

$classes = $variants[$variant] ?? $variants['primary'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<button type="{{ $type }}"
        {{ $attributes->merge(['class' => "font-medium rounded-lg focus:outline-none {$sizeClass} {$classes}"]) }}>
    {{ $slot }}
</button>