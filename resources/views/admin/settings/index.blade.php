@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Settings</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('admin.settings.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">General Settings</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Application Name</label>
                                <input type="text" name="app_name" class="form-control" value="{{ config('app.name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Application URL</label>
                                <input type="text" class="form-control" value="{{ config('app.url') }}" disabled>
                                <small class="text-muted">Change in .env file</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Store Settings</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Currency Code</label>
                                <input type="text" name="app_currency" class="form-control" value="{{ config('app.currency') }}" placeholder="USD, EUR, GBP" required>
                                <small class="text-muted">e.g., USD, EUR, GBP, EGP</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="app_currency_symbol" class="form-control" value="{{ config('app.currency_symbol') }}" placeholder="$" required>
                                <small class="text-muted">e.g., $, €, £, EGP</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" name="app_tax_rate" class="form-control" value="{{ config('app.tax_rate') }}" step="0.01" min="0" max="100" required>
                                <small class="text-muted">Default tax rate for sales</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Contact Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Business Address</label>
                                <textarea name="app_address" class="form-control" rows="2">{{ config('app.address') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="app_phone" class="form-control" value="{{ config('app.phone') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="app_email" class="form-control" value="{{ config('app.email') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
