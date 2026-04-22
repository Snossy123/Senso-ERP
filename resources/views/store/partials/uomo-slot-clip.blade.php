{{-- Clipped iframe MVP: shows top portion of a full Uomo HTML page (navbar / hero preview). --}}
@props([
    'url' => '',
    'clipHeight' => 180,
    'iframeHeight' => 720,
])
@if($url !== '')
    <div class="store-uomo-slot-clip w-100" style="height: {{ (int) $clipHeight }}px; overflow: hidden; position: relative;">
        <iframe
            src="{{ $url }}"
            title="Uomo layout"
            class="w-100 border-0 position-absolute top-0 start-0"
            style="height: {{ (int) $iframeHeight }}px;"
            loading="lazy"
        ></iframe>
    </div>
@endif
