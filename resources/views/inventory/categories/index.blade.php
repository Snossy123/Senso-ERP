@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Categories</span>
    </div>
</div>
@endsection
@section('content')
<div class="row row-sm">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title">Add New Category</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.categories.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Name <span class="tx-danger">*</span></label>
                        <input class="form-control" name="name" type="text" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">None (Top Level)</option>
                            @foreach($categories->where('parent_id', null) as $pc)
                                <option value="{{ $pc->id }}">{{ $pc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                            <tr>
                                <td>{{ $cat->name }}</td>
                                <td>{{ $cat->parent?->name ?? '—' }}</td>
                                <td><code>{{ $cat->slug }}</code></td>
                                <td><span class="badge badge-{{ $cat->is_active ? 'success' : 'danger' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <form action="{{ route('inventory.categories.destroy', $cat) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger ml-2" onclick="return confirm('Delete category?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
