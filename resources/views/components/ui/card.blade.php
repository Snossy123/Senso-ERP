@props([
    'title' => null,
    'elevated' => false,
])

<div {{ $attributes->class(['pos-surface', 'pos-surface--elevated' => $elevated]) }}>
    @if ($title)
        <div class="pos-surface__header">{{ $title }}</div>
    @endif
    <div class="pos-surface__body">
        {{ $slot }}
    </div>
</div>
