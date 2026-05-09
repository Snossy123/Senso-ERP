@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'error' => false,
])

@php
    $inputId = $attributes->get('id') ?? 'pos-input-'.$name;
@endphp

<div class="pos-field">
    @if ($label)
        <label class="pos-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($value !== null) value="{{ $value }}" @endif
        {{ $attributes->class(['pos-input', 'pos-input--error' => $error]) }}
    />
</div>
