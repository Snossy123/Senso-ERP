@php($title = $heroPayload['title'] ?? 'Storefront')
@php($subtitle = $heroPayload['subtitle'] ?? 'Builder-powered ecommerce experience.')

<section class="hero-section text-center text-md-start">
    <div class="container py-lg-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">{{ $title }}</h1>
                <p class="lead mb-0 text-white">{{ $subtitle }}</p>
            </div>
        </div>
    </div>
</section>
