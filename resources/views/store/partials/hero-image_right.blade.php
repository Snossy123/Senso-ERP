@php($title = $heroPayload['title'] ?? 'Storefront')
@php($subtitle = $heroPayload['subtitle'] ?? 'Builder-powered ecommerce experience.')

<section class="py-5" style="background: linear-gradient(135deg, #eef2ff 0%, #ffffff 55%);">
    <div class="container py-lg-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="display-5 fw-bold mb-3">{{ $title }}</h1>
                <p class="lead text-muted mb-0">{{ $subtitle }}</p>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/img/media/login.png') }}" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</section>
