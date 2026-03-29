@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Users / Add New</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title">Create New User</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-control @error('role_id') is-invalid @enderror" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>
                    </div>
                    @if($branches->count() > 0)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">No Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="form-group">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="is_active" value="1" checked>
                            <span class="custom-control-label">Active User</span>
                        </label>
                    </div>
                    <hr>
                    <h5>Permissions (inherited from role, can override)</h5>
                    @if(!empty($permissionsGrouped))
                        @foreach($permissionsGrouped as $groupKey => $group)
                            <div class="card mb-2">
                                <div class="card-header py-2 bg-light">
                                    <strong>{{ $group['name'] ?? ucfirst($groupKey) }}</strong>
                                </div>
                                <div class="card-body py-2">
                                    @foreach($group['permissions'] as $permission)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}">
                                            <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
