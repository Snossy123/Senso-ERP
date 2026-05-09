@props([
    'tone' => 'neutral',
])

@php
    $toneClass = in_array($tone, ['neutral', 'success', 'warning', 'danger', 'primary'], true)
        ? 'pos-badge--'.$tone
        : 'pos-badge--neutral';
@endphp

<span {{ $attributes->class(['pos-badge', $toneClass]) }}>{{ $slot }}</span>
