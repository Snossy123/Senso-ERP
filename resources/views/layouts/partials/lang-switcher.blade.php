@php
    $supported = config('locales.supported', []);
    $current = app()->getLocale();
    $currentMeta = $supported[$current] ?? reset($supported);
    $isRtlLayout = ($dir ?? 'ltr') === 'rtl';
@endphp
@once
<style>
.locale-switch.drop-flag { margin-right: 0.75rem !important; }
.locale-switch.drop-flag .country-flag1 { padding: 0.35rem 0.4rem; }
.locale-switch .locale-switch-menu {
  z-index: 1060;
  top: calc(100% + 0.45rem) !important;
  right: 0 !important;
  left: auto !important;
  margin-top: 0 !important;
  width: auto !important;
  min-width: 12rem !important;
  max-width: min(18rem, 92vw);
  padding: 0.35rem 0;
  border-radius: 0.5rem;
  border: 1px solid rgba(1, 98, 232, 0.12);
  box-shadow: 0 0.5rem 1.25rem rgba(28, 39, 60, 0.12);
}
.locale-switch.locale-switch--rtl .locale-switch-menu {
  right: auto !important;
  left: 0 !important;
}
.locale-switch .locale-switch-menu::before,
.locale-switch .locale-switch-menu::after { content: none !important; display: none !important; }
.locale-switch-item {
  gap: 0.65rem;
  min-height: 2.5rem;
  font-size: 0.9rem;
  line-height: 1.2;
}
.locale-switch-item .locale-switch-flag-img {
  width: 1.5rem;
  height: 1.05rem;
  object-fit: cover;
  border-radius: 2px;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);
  flex-shrink: 0;
}
.locale-switch-item .locale-switch-label { flex: 1; min-width: 0; }
@media (max-width: 576px) {
  .drop-flag > a.country-flag1 { display: flex !important; }
}
</style>
@endonce
<li class="nav-item">
    <div class="dropdown nav-item drop-flag position-relative locale-switch @if($isRtlLayout) locale-switch--rtl @endif">
        <a href="#" class="d-flex align-items-center nav-link pl-0 pr-1 country-flag1" aria-expanded="false" aria-haspopup="true">
            <span class="avatar mr-0 align-self-center bg-transparent" style="line-height: 0;">
                <img class="locale-switch-flag-img" src="{{ URL::asset($currentMeta['flag'] ?? 'assets/plugins/flag-icon-css/flags/4x3/us.svg') }}" width="22" height="15" alt="">
            </span>
            <strong class="mx-2 my-auto text-truncate d-none d-sm-inline" style="max-width: 6rem;">{{ $currentMeta['native'] ?? strtoupper($current) }}</strong>
        </a>
        <div class="dropdown-menu locale-switch-menu">
            @foreach ($supported as $code => $meta)
                <a href="{{ route('locale.switch', ['locale' => $code]) }}" class="dropdown-item locale-switch-item d-flex align-items-center {{ $code === $current ? 'active' : '' }}" @if($code === $current) aria-current="true" @endif>
                    <img class="locale-switch-flag-img" src="{{ URL::asset($meta['flag']) }}" width="22" height="15" alt="" loading="lazy">
                    <span class="locale-switch-label text-body">{{ $meta['native'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</li>
