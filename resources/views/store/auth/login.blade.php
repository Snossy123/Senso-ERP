@extends('store.layouts.portal')
@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-5">
        <div class="card border-0 shadow-lg rounded-5 p-5 bg-white">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-primary">Welcome Back</h2>
                <p class="text-muted">Sign in to your customer account</p>
            </div>
            <form action="{{ route('store.login') }}" method="POST">
                @csrf
                @if($errors->any())
                    <div class="alert alert-danger rounded-4 mb-4">
                        <ul class="mb-0 small">@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif
                <div class="mb-4">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control rounded-pill px-4 bg-light border-0 py-3" placeholder="name@example.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-4 bg-light border-0 py-3" placeholder="••••••••" required>
                </div>
                <div class="mb-4 d-flex justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label text-muted" for="remember">Remember me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-premium btn-lg w-100 py-3 rounded-pill shadow-sm">Sign In</button>
            </form>
            <div class="text-center mt-5">
                <p class="text-muted">Don't have an account? <a href="{{ route('store.register') }}" class="text-primary fw-bold text-decoration-none">Create Account</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
