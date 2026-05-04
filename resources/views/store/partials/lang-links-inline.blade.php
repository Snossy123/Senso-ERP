@foreach (config('locales.supported', []) as $code => $meta)
    <a class="text-decoration-none text-muted small {{ app()->getLocale() === $code ? 'fw-bold text-primary' : '' }}"
       href="{{ route('locale.switch', ['locale' => $code]) }}">{{ $meta['native'] }}</a>@if (! $loop->last)<span class="text-muted px-1">|</span>@endif
@endforeach
