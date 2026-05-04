@php
    $supported = config('locales.supported', []);
    $current = app()->getLocale();
@endphp
<li class="nav-item dropdown me-2">
    <a class="nav-link dropdown-toggle" href="#" id="storeLangDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $supported[$current]['native'] ?? strtoupper($current) }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="storeLangDropdown">
        @foreach ($supported as $code => $meta)
            <li>
                <a class="dropdown-item {{ $code === $current ? 'active' : '' }}" href="{{ route('locale.switch', ['locale' => $code]) }}">
                    {{ $meta['native'] }}
                </a>
            </li>
        @endforeach
    </ul>
</li>
