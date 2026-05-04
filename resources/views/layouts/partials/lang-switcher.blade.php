@php
    $supported = config('locales.supported', []);
    $current = app()->getLocale();
    $currentMeta = $supported[$current] ?? reset($supported);
@endphp
<li class="">
    <div class="dropdown nav-itemd-none d-md-flex">
        <a href="#" class="d-flex nav-item nav-link pl-0 country-flag1" data-toggle="dropdown" aria-expanded="false">
            <span class="avatar country-Flag mr-0 align-self-center bg-transparent">
                <img src="{{ URL::asset('assets/img/flags/' . ($currentMeta['flag'] ?? 'us_flag.jpg')) }}" alt="">
            </span>
            <div class="my-auto">
                <strong class="mr-2 ml-2 my-auto">{{ $currentMeta['native'] ?? strtoupper($current) }}</strong>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-left dropdown-menu-arrow" x-placement="bottom-end">
            @foreach ($supported as $code => $meta)
                <a href="{{ route('locale.switch', ['locale' => $code]) }}" class="dropdown-item d-flex {{ $code === $current ? 'active' : '' }}">
                    <span class="avatar ml-3 align-self-center bg-transparent">
                        <img src="{{ URL::asset('assets/img/flags/' . $meta['flag']) }}" alt="">
                    </span>
                    <div class="d-flex">
                        <span class="mt-2">{{ $meta['native'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</li>
