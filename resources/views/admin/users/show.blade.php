@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Users / Details</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary mr-2"><i class="fa fa-edit"></i> Edit User</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-xxl bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                    <span class="text-white display-4 font-weight-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-3">{{ $user->role?->name ?? 'Staff' }}</p>
                <div class="d-flex justify-content-center mb-4">
                    @if($user->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-danger">Inactive</span>
                    @endif
                </div>
                <hr>
                <div class="text-left mt-3">
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Phone:</strong> {{ $user->phone ?? '-' }}</p>
                    <p><strong>Branch:</strong> {{ $user->branch?->name ?? 'N/A' }}</p>
                    <p><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header pb-0 mt-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Permissions</h4>
                    <span class="badge badge-primary">Total: {{ count($user->all_permissions ?? []) }}</span>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($user->all_permissions))
                    <div class="row">
                        @foreach(collect($user->all_permissions)->chunk(floor(count($user->all_permissions) / 2) + 1) as $chunk)
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    @foreach($chunk as $perm)
                                        <li class="mb-1"><i class="fa fa-check-circle text-success mr-2"></i> {{ ucfirst(str_replace('.', ' ', $perm)) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-muted m-5">No specific permissions assigned.</p>
                @endif
            </div>
        </div>

        @if(isset($activity) && !empty($activity['activities']))
        <div class="card">
            <div class="card-header pb-0 mt-2">
                <h4 class="card-title">Recent Activity</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activity['activities'] as $item)
                            <tr>
                                <td><span class="badge badge-light">{{ strtoupper($item->action) }}</span></td>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->created_at->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
