@php($title = $heroPayload['title'] ?? 'Storefront')
@php($subtitle = $heroPayload['subtitle'] ?? 'Builder-powered ecommerce experience.')

<section class="py-5 bg-white border-bottom">
    <div class="container text-center py-lg-4">
        <h1 class="display-6 fw-bold mb-3">{{ $title }}</h1>
        <p class="lead text-muted mb-0 mx-auto" style="max-width: 720px;">{{ $subtitle }}</p>
    </div>
</section>
