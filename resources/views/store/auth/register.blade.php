@extends('store.layouts.portal')
@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-6">
        <div class="card border-0 shadow-lg rounded-5 p-5 bg-white">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-primary">Create Your Account</h2>
                <p class="text-muted">Join the Senso community today</p>
            </div>
            <form action="{{ route('store.register') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control rounded-pill px-4 bg-light border-0 py-3 mt-1" placeholder="Ex: John Doe" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-pill px-4 bg-light border-0 py-3 mt-1" placeholder="name@example.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control rounded-pill px-4 bg-light border-0 py-3 mt-1" placeholder="+1 (555) 000-0000" value="{{ old('phone') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill px-4 bg-light border-0 py-3 mt-1" placeholder="Minimum 8 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control rounded-pill px-4 bg-light border-0 py-3 mt-1" placeholder="••••••••" required>
                    </div>
                    <div class="col-12 mt-5">
                       <button type="submit" class="btn btn-premium btn-lg w-100 py-3 rounded-pill shadow-lg">Register Now <i class="fa fa-user-plus ms-2"></i></button>
                    </div>
                </div>
            </form>
            <div class="text-center mt-5">
                <p class="text-muted">Already have an account? <a href="{{ route('store.login') }}" class="text-primary fw-bold text-decoration-none">Sign In</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
