@php($title = $heroPayload['title'] ?? 'Storefront')
@php($subtitle = $heroPayload['subtitle'] ?? 'Builder-powered ecommerce experience.')
@php($ctaLabel = data_get($ctaPayload, 'label', 'Start Shopping'))
@php($ctaUrl = data_get($ctaPayload, 'url', '/store'))

<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-3">
        <div class="p-4 p-lg-5 bg-white rounded-4 shadow-sm">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <h1 class="display-6 fw-bold mb-2">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-premium btn-lg w-100 w-lg-auto" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
