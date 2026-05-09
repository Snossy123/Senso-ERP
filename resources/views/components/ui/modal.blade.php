@props([
    'title' => null,
])

<div {{ $attributes->class(['pos-ui-modal-frame']) }}>
    @if ($title)
        <div class="pos-ui-modal-frame__header">{{ $title }}</div>
    @endif
    <div class="pos-ui-modal-frame__body">
        {{ $slot }}
    </div>
</div>
