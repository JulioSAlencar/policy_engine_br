@props([
    'variant' => 'primary',
    'size'    => 'md',
    'href'    => null,
    'type'    => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-1 rounded-lg font-medium '
          . 'transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 '
          . 'focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $variants = [
        'primary'      => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-400',
        'secondary'    => 'bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-300',
        'danger'       => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-400',
        'success'      => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-400',
        'soft'         => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-300',
        'soft-danger'  => 'bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-300',
        'soft-success' => 'bg-green-50 text-green-700 hover:bg-green-100 focus:ring-green-300',
        'soft-indigo'  => 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 focus:ring-indigo-300',
    ];

    $classes = trim(
        $base . ' '
        . ($sizes[$size] ?? $sizes['md']) . ' '
        . ($variants[$variant] ?? $variants['primary'])
    );
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
