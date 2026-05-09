@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $variantClass = in_array($variant, ['primary', 'secondary', 'ghost', 'danger'], true)
        ? 'pos-btn--'.$variant
        : 'pos-btn--primary';
    $sizeClass = in_array($size, ['sm', 'lg'], true) ? 'pos-btn--'.$size : 'pos-btn--md';
    $classes = 'pos-btn '.$variantClass.' '.$sizeClass;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
