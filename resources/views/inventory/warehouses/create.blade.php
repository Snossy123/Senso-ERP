@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Warehouses / Add New</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title">Add New Warehouse</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.warehouses.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Name <span class="tx-danger">*</span></label>
                        <input class="form-control" name="name" value="{{ old('name') }}" type="text" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input class="form-control" name="location" value="{{ old('location') }}" type="text" placeholder="Address or location">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Manager Name</label>
                        <input class="form-control" name="manager_name" value="{{ old('manager_name') }}" type="text">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="{{ old('phone') }}" type="text">
                    </div>
                    <div class="form-group">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="is_active" value="1" checked>
                            <span class="custom-control-label">Active</span>
                        </label>
                    </div>
                    <div class="form-footer mt-2">
                        <button type="submit" class="btn btn-primary">Save Warehouse</button>
                        <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
