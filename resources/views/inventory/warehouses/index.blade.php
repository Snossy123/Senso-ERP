@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Warehouses</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('inventory.warehouses.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add Warehouse</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Warehouses</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Manager</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouses as $warehouse)
                            <tr>
                                <td><strong>{{ $warehouse->name }}</strong></td>
                                <td>{{ $warehouse->location ?? '-' }}</td>
                                <td>{{ $warehouse->manager_name ?? '-' }}</td>
                                <td>{{ $warehouse->phone ?? '-' }}</td>
                                <td><span class="badge badge-light">{{ $warehouse->products->count() }}</span></td>
                                <td>
                                    @if($warehouse->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('inventory.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-primary-light"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('inventory.warehouses.destroy', $warehouse) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger-light" onclick="return confirm('Delete warehouse?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted">No warehouses found</p>
                                    <a href="{{ route('inventory.warehouses.create') }}" class="btn btn-primary">Add First Warehouse</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
