@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Suppliers / Add New</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title">Add New Supplier</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.suppliers.store') }}" method="POST">
                    @csrf
                    <div class="row row-sm">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Name <span class="tx-danger">*</span></label>
                                <input class="form-control" name="name" value="{{ old('name') }}" type="text" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input class="form-control" name="email" value="{{ old('email') }}" type="email">
                            </div>
                        </div>
                    </div>
                    <div class="row row-sm">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input class="form-control" name="phone" value="{{ old('phone') }}" type="text">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Tax Number</label>
                                <input class="form-control" name="tax_number" value="{{ old('tax_number') }}" type="text">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
                    </div>
                    <div class="row row-sm">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input class="form-control" name="city" value="{{ old('city') }}" type="text">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <input class="form-control" name="country" value="{{ old('country') }}" type="text">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="is_active" value="1" checked>
                            <span class="custom-control-label">Active</span>
                        </label>
                    </div>
                    <div class="form-footer mt-2">
                        <button type="submit" class="btn btn-primary">Save Supplier</button>
                        <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
