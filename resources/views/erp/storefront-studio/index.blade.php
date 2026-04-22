@extends('layouts.master')
@section('title', 'Visual Store Studio')

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Visual Store Studio</h2>
                <p class="mg-b-0">Edit page schema v2, preview the live store, and pull ERP catalog samples.</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small">Page:</span>
            @foreach($pageTypes as $pt)
                <a class="btn btn-sm {{ $pt === $initialPageType ? 'btn-primary' : 'btn-outline-secondary' }}"
                   href="{{ route('admin.storefront-studio.index', ['page' => $pt]) }}">{{ $pt }}</a>
            @endforeach
            <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ route('admin.storefront-builder.index') }}">Classic builder</a>
        </div>
    </div>
    <div
        id="storefront-studio-root"
        data-base="{{ url('/admin/storefront-studio') }}"
        data-page="{{ $initialPageType }}"
        data-preview="{{ $storePreviewUrl }}"
    ></div>
    @unless(file_exists(public_path('js/storefront-studio.js')))
        <div class="alert alert-warning mt-3">
            Compile the studio bundle: <code>npm install</code> then <code>npm run dev</code> (or <code>npm run production</code>).
        </div>
    @endunless
@endsection

@section('js')
    @if(file_exists(public_path('js/storefront-studio.js')))
        <script src="{{ asset('js/storefront-studio.js') }}?v={{ @filemtime(public_path('js/storefront-studio.js')) }}"></script>
    @endif
@endsection
