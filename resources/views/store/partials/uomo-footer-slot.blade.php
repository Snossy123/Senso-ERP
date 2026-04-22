{{-- Shows bottom strip of full Uomo page (footer region MVP). --}}
@props([
    'url' => '',
    'clipHeight' => 320,
    'iframeHeight' => 1400,
])
@if($url !== '')
    <div class="store-uomo-footer-slot w-100 d-flex flex-column justify-content-end" style="height: {{ (int) $clipHeight }}px; overflow: hidden;">
        <iframe
            src="{{ $url }}"
            title="Uomo footer"
            class="w-100 border-0"
            style="height: {{ (int) $iframeHeight }}px; flex-shrink: 0;"
            loading="lazy"
        ></iframe>
    </div>
@endif
