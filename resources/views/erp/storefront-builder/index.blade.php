@extends('layouts.master')
@section('title', 'Storefront Builder')

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Storefront Builder</h2>
                <p class="mg-b-0">Build, preview, and publish ecommerce storefront templates.</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $activePage = $storefront->pages->firstWhere('page_type', $activePageType);
        $activeTemplate = collect($templates)->firstWhere('key', $storefront->active_template_key);
        $publishedVersion = $storefront->versions->firstWhere('id', $storefront->published_version_id);
    @endphp

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div>
                <div class="text-muted small">Storefront status</div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $storefront->status === 'published' ? 'success' : 'warning' }}">{{ strtoupper($storefront->status) }}</span>
                    @if($publishedVersion)
                        <span class="text-muted small">Published: v{{ $publishedVersion->version }} @ {{ optional($publishedVersion->published_at)->format('Y-m-d H:i') }}</span>
                    @else
                        <span class="text-muted small">No published version yet</span>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted small">Active template</div>
                <div class="fw-semibold">{{ $storefront->active_template_key }}</div>
                @if($activeTemplate)
                    <a class="small" href="{{ $activeTemplate['preview'] }}" target="_blank">Open Uomo preview</a>
                @endif
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        @foreach($pageTypes as $pageType)
            <li class="nav-item">
                <a class="nav-link {{ $pageType === $activePageType ? 'active' : '' }}" href="{{ route('admin.storefront-builder.index', ['page' => $pageType]) }}">
                    {{ \Illuminate\Support\Str::title(str_replace('-', ' ', $pageType)) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">Storefront Settings</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.storefront-builder.update') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_page" value="{{ $activePageType }}">
                        <div class="mb-3">
                            <label class="form-label">Storefront Name</label>
                            <input class="form-control" name="name" value="{{ old('name', $storefront->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Active Uomo Template</label>
                            <select class="form-select" name="active_template_key" required>
                                @foreach($templates as $template)
                                    <option value="{{ $template['key'] }}" @selected($template['key'] === $storefront->active_template_key)>
                                        {{ $template['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Navbar style (legacy)</label>
                            <select class="form-select" name="settings[navbar_variant]">
                                @php($nav = old('settings.navbar_variant', data_get($storefront->settings, 'navbar_variant', 'glass_sticky')))
                                <option value="glass_sticky" @selected($nav === 'glass_sticky')>Glass sticky (default)</option>
                                <option value="solid_light" @selected($nav === 'solid_light')>Solid light</option>
                                <option value="dark_floating" @selected($nav === 'dark_floating')>Dark floating</option>
                                <option value="centered_brand" @selected($nav === 'centered_brand')>Centered brand</option>
                            </select>
                            <div class="form-text">Used only when no Uomo global navbar is selected below.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hero style (legacy)</label>
                            <select class="form-select" name="settings[hero_variant]">
                                @php($heroVariant = old('settings.hero_variant', data_get($storefront->settings, 'hero_variant', 'gradient_split')))
                                <option value="gradient_split" @selected($heroVariant === 'gradient_split')>Gradient split (default)</option>
                                <option value="minimal_center" @selected($heroVariant === 'minimal_center')>Minimal centered</option>
                                <option value="image_right" @selected($heroVariant === 'image_right')>Image accent (right)</option>
                                <option value="boxed_cta" @selected($heroVariant === 'boxed_cta')>Boxed + CTA strip</option>
                            </select>
                            <div class="form-text">Used only when Home → Uomo hero slot is not set (Home tab).</div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary mb-2">Step 1 — Global navbar (Uomo 1–23)</h6>
                        <p class="small text-muted mb-3">Same header family as each purchased home demo. Applies to all storefront pages.</p>
                        @php($slotNav = old('settings.layout_slots.global.navbar', data_get($storefront->settings, 'layout_slots.global.navbar')))
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label class="d-flex align-items-center gap-2 border rounded p-2 mb-0">
                                    <input class="form-check-input mt-0" type="radio" name="settings[layout_slots][global][navbar]" value="" @checked($slotNav === null || $slotNav === '')>
                                    <span>Legacy ERP navbar (use style dropdown above)</span>
                                </label>
                            </div>
                            @foreach($navbarChoices as $t)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <label class="border rounded p-2 d-block small h-100 mb-0" style="cursor: pointer;">
                                        <div class="d-flex align-items-start gap-2">
                                            <input class="form-check-input mt-1" type="radio" name="settings[layout_slots][global][navbar]" value="{{ $t['key'] }}" @checked($slotNav === $t['key'])>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $t['name'] }}</div>
                                                <a class="text-primary" href="{{ url($t['preview']) }}" target="_blank" rel="noopener" onclick="event.stopPropagation();">Open demo</a>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @if($activePageType === 'home')
                            <hr class="my-4">
                            <h6 class="text-primary mb-2">Step 2 — Home hero (Uomo 1–23)</h6>
                            <p class="small text-muted mb-3">Clipped top of the selected demo page. When set, legacy hero styles are ignored for Home.</p>
                            @php($slotHero = old('settings.layout_slots.pages.home.hero', data_get($storefront->settings, 'layout_slots.pages.home.hero')))
                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="d-flex align-items-center gap-2 border rounded p-2 mb-0">
                                        <input class="form-check-input mt-0" type="radio" name="settings[layout_slots][pages][home][hero]" value="" @checked($slotHero === null || $slotHero === '')>
                                        <span>Legacy ERP hero (builder section + style dropdown)</span>
                                    </label>
                                </div>
                                @foreach($navbarChoices as $t)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <label class="border rounded p-2 d-block small h-100 mb-0" style="cursor: pointer;">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="radio" name="settings[layout_slots][pages][home][hero]" value="{{ $t['key'] }}" @checked($slotHero === $t['key'])>
                                                <div class="fw-semibold">{{ $t['name'] }}</div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <hr class="my-4">
                            <h6 class="text-primary mb-2">Step 3 — Home footer (Uomo 1–23)</h6>
                            <p class="small text-muted mb-3">Shows a clipped bottom region of the selected demo. When set, the default ERP footer is replaced on Home.</p>
                            @php($slotFooter = old('settings.layout_slots.pages.home.footer', data_get($storefront->settings, 'layout_slots.pages.home.footer')))
                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="d-flex align-items-center gap-2 border rounded p-2 mb-0">
                                        <input class="form-check-input mt-0" type="radio" name="settings[layout_slots][pages][home][footer]" value="" @checked($slotFooter === null || $slotFooter === '')>
                                        <span>Legacy ERP footer</span>
                                    </label>
                                </div>
                                @foreach($navbarChoices as $t)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <label class="border rounded p-2 d-block small h-100 mb-0" style="cursor: pointer;">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="radio" name="settings[layout_slots][pages][home][footer]" value="{{ $t['key'] }}" @checked($slotFooter === $t['key'])>
                                                <div class="fw-semibold">{{ $t['name'] }}</div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Publishing</div>
                <div class="card-body d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.storefront-builder.publish') }}">
                        @csrf
                        <input type="hidden" name="return_page" value="{{ $activePageType }}">
                        <button class="btn btn-success">Publish Current Draft</button>
                    </form>
                    <a class="btn btn-outline-primary" href="{{ route('admin.storefront-builder.preview', ['page_type' => $activePageType]) }}" target="_blank">
                        Preview {{ \Illuminate\Support\Str::title(str_replace('-', ' ', $activePageType)) }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">Page Builder — {{ \Illuminate\Support\Str::title(str_replace('-', ' ', $activePageType)) }}</div>
                <div class="card-body">
                    @if(!$activePage)
                        <div class="alert alert-warning mb-0">This page is not provisioned yet.</div>
                    @else
                    <form id="builder-sections-form" method="POST" action="{{ route('admin.storefront-builder.sections.update') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="page_type" value="{{ $activePage->page_type }}">
                        @foreach($activePage->sections->sortBy('sort_order')->values() as $idx => $section)
                            <div class="border rounded p-3 mb-3 builder-section-row" draggable="true">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="text-muted" title="Drag to reorder" style="cursor: grab;">
                                            <i class="fe fe-menu"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $section->section_type }}</div>
                                            <div class="text-muted small">{{ $section->section_key }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div>
                                            <label class="form-label small mb-1">Order</label>
                                            <input class="form-control form-control-sm" style="width: 90px" type="number" name="sections[{{ $idx }}][sort_order]" value="{{ old('sections.'.$idx.'.sort_order', $section->sort_order) }}" min="1">
                                        </div>
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="hidden" name="sections[{{ $idx }}][is_enabled]" value="0">
                                            <input class="form-check-input" type="checkbox" name="sections[{{ $idx }}][is_enabled]" value="1" @checked($section->is_enabled)>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="sections[{{ $idx }}][section_key]" value="{{ $section->section_key }}">

                                @if($section->section_type === 'hero')
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">Hero title</label>
                                            <input class="form-control" name="sections[{{ $idx }}][payload][title]" value="{{ old('sections.'.$idx.'.payload.title', data_get($section->payload, 'title')) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">Hero subtitle</label>
                                            <input class="form-control" name="sections[{{ $idx }}][payload][subtitle]" value="{{ old('sections.'.$idx.'.payload.subtitle', data_get($section->payload, 'subtitle')) }}">
                                        </div>
                                    </div>
                                @endif

                                @if($section->section_type === 'cta')
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">CTA label</label>
                                            <input class="form-control" name="sections[{{ $idx }}][payload][label]" value="{{ old('sections.'.$idx.'.payload.label', data_get($section->payload, 'label')) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">CTA URL</label>
                                            <input class="form-control" name="sections[{{ $idx }}][payload][url]" value="{{ old('sections.'.$idx.'.payload.url', data_get($section->payload, 'url')) }}">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <button class="btn btn-primary">Update Sections</button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Published Versions</div>
                <div class="card-body">
                    @forelse($storefront->versions->sortByDesc('version') as $version)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong>v{{ $version->version }}</strong>
                                <div class="text-muted small">{{ optional($version->published_at)->format('Y-m-d H:i') }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.storefront-builder.rollback') }}">
                                @csrf
                                <input type="hidden" name="return_page" value="{{ $activePageType }}">
                                <input type="hidden" name="version" value="{{ $version->version }}">
                                <button class="btn btn-sm btn-outline-warning">Rollback</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No versions yet. Publish first version.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(() => {
  const form = document.getElementById('builder-sections-form');
  if (!form) return;

  let dragEl = null;

  const getRows = () => Array.from(form.querySelectorAll('.builder-section-row'));

  const renumber = () => {
    getRows().forEach((row, idx) => {
      const orderInput = row.querySelector('input[name^="sections["][name$="[sort_order]"]');
      if (orderInput) orderInput.value = String(idx + 1);

      row.querySelectorAll('[name^="sections["]').forEach((el) => {
        const name = el.getAttribute('name');
        if (!name) return;
        el.setAttribute('name', name.replace(/^sections\[\d+]/, `sections[${idx}]`));
      });
    });
  };

  form.addEventListener('dragstart', (e) => {
    const row = e.target.closest('.builder-section-row');
    if (!row) return;
    dragEl = row;
    e.dataTransfer.effectAllowed = 'move';
  });

  form.addEventListener('dragover', (e) => {
    const row = e.target.closest('.builder-section-row');
    if (!row || !dragEl || row === dragEl) return;
    e.preventDefault();
    const rect = row.getBoundingClientRect();
    const before = (e.clientY - rect.top) < rect.height / 2;
    row.parentNode.insertBefore(dragEl, before ? row : row.nextSibling);
  });

  form.addEventListener('drop', (e) => e.preventDefault());

  form.addEventListener('dragend', () => {
    dragEl = null;
    renumber();
  });
})();
</script>
@endsection
