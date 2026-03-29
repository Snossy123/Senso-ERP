@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Settings</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Units of Measure</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Add New Unit</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.units.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">Unit Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Kilogram" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Short Name (Code)</label>
                        <input type="text" name="short_name" class="form-control" placeholder="e.g. KG" required>
                    </div>
                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary btn-block">Create Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Defined Units</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($units as $unit)
                            <tr>
                                <td>{{ $unit->name }}</td>
                                <td><span class="badge badge-light px-2">{{ $unit->short_name }}</span></td>
                                <td class="text-right">
                                    <form action="{{ route('inventory.units.destroy', $unit) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger-light" onclick="return confirm('Delete unit?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No units defined yet.</td>
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
