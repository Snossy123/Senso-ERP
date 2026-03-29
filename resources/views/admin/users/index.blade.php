@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Users</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add User</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">System Users</h4>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <form action="{{ route('admin.users.index') }}" method="GET" class="row">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="role" class="form-control">
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" {{ request('role') == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($branches->count() > 0)
                        <div class="col-md-2">
                            <select name="branch" class="form-control">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <select name="is_active" class="form-control">
                                <option value="">Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search"></i></button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                        </div>
                    </form>
                </div>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center mr-2" style="width: 35px; height: 35px;">
                                            <span class="text-white font-weight-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <strong>{{ $user->name }}</strong>
                                        @if($user->id === auth()->id())
                                            <span class="badge badge-info ml-2">You</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->role?->slug === 'admin')
                                        <span class="badge badge-danger">Admin</span>
                                    @elseif($user->role?->slug === 'manager')
                                        <span class="badge badge-warning">Manager</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $user->role?->name ?? 'Staff' }}</span>
                                    @endif
                                </td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                 <td>
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-info-light"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary-light"><i class="fa fa-edit"></i></a>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline delete-form" data-name="{{ $user->name }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger-light"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted">No users found</p>
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add First User</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function() {
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var name = form.data('name');

        if (confirm('Are you sure you want to delete user "' + name + '"?')) {
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        form.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                        // Optional: Show toast message
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.error : 'Error deleting user';
                    alert(msg);
                }
            });
        }
    });
});
</script>
@endsection
