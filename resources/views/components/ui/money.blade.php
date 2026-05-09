@props([
    'amount',
    'currency' => null,
    'size' => 'md',
    'tone' => 'default',
])

@php
    $currency = $currency ?? config('app.currency_symbol', '$');
    $valid = $amount !== null && $amount !== '' && is_numeric($amount) && is_finite((float) $amount);
    $formatted = $valid ? number_format((float) $amount, 2, '.', ',') : null;

    $sizeClasses = [
        'sm' => 'pos-money--sm',
        'md' => 'pos-money--md',
        'lg' => 'pos-money--lg',
        'hero' => 'pos-money--hero',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    $toneClasses = [
        'default' => '',
        'muted' => 'pos-money--muted',
        'success' => 'pos-money--success',
        'danger' => 'pos-money--danger',
    ];
    $toneClass = $toneClasses[$tone] ?? '';
@endphp

<span {{ $attributes->class(['pos-money', 'pos-tabular', $sizeClass, $toneClass]) }}>
    @if ($formatted !== null)
        {{ $currency }}{{ $formatted }}
    @else
        —
    @endif
</span>
