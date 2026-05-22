@extends('layouts.master2')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow mt-5">
                <div class="card-body p-4">
                    <h4 class="mb-3">{{ __('auth_pages.change_password.title') }}</h4>
                    <p class="text-muted small">{{ __('auth_pages.change_password.subtitle') }}</p>
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('password.change.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('auth_pages.change_password.new_password') }}</label>
                            <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('auth_pages.change_password.confirm') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('auth_pages.change_password.submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
