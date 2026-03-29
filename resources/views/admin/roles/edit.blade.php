@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Roles / Edit</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card shadow-md border-0">
            <div class="card-header pb-0 mt-3 d-flex justify-content-between align-items-center">
                <h4 class="card-title">Edit Role: <span class="text-primary">{{ $role->name }}</span></h4>
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" form="editRoleForm" value="1" id="is_active" {{ $role->is_active ? 'checked' : '' }}>
                    <label class="form-check-label font-weight-bold ml-2" for="is_active">Role is Active</label>
                </div>
            </div>
            <div class="card-body">
                <form id="editRoleForm" action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group border-bottom pb-2">
                                <label class="text-muted tx-11 font-weight-bold mb-1">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control border-0 px-0 pt-0 font-weight-bold tx-16" required value="{{ $role->name }}" placeholder="e.g. Sales Associate">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group border-bottom pb-2">
                                <label class="text-muted tx-11 font-weight-bold mb-1">Description</label>
                                <textarea name="description" id="description" class="form-control border-0 px-0 pt-0 tx-14" rows="1" placeholder="Short description of this role">{{ $role->description }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 d-flex align-items-center">
                        <i class="fa fa-info-circle mr-2 tx-18"></i>
                        <span class="tx-11">Assigning permissions here will update the access for all users assigned to this role ({{ $role->users()->count() }} users affected).</span>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 font-weight-bold">Assign Permissions</h5>
                        <div class="btn-group btn-group-sm rounded-pill overflow-hidden border">
                            <button type="button" class="btn btn-light px-3" id="selectAll">Select All</button>
                            <button type="button" class="btn btn-light px-3" id="deselectAll">Deselect All</button>
                        </div>
                    </div>

                    @if(!empty($permissionsGrouped))
                        @php $rolePermissions = $role->permissions->pluck('id')->toArray(); @endphp
                        <div class="row">
                        @foreach($permissionsGrouped as $groupKey => $group)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-light border-0 shadow-sm rounded-10">
                                    <div class="card-header py-2 bg-gradient-light d-flex justify-content-between align-items-center">
                                        <strong class="tx-13 text-dark">{{ $group['name'] ?? ucfirst($groupKey) }}</strong>
                                        <div class="custom-control custom-checkbox pt-1">
                                            <input type="checkbox" class="custom-control-input group-check" data-group="{{ $groupKey }}" id="group_{{ $groupKey }}">
                                            <label class="custom-control-label" for="group_{{ $groupKey }}"></label>
                                        </div>
                                    </div>
                                    <div class="card-body py-3">
                                        @foreach($group['permissions'] as $permission)
                                            <div class="form-check custom-checkbox-modern mb-2">
                                                <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-group="{{ $groupKey }}" id="perm_{{ $permission->id }}" {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label tx-12" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @endif

                    <div class="form-footer mt-5 text-right">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light btn-lg px-4 mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm">Update Role & Permissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.rounded-10 { border-radius: 10px !important; }
.bg-gradient-light { background: linear-gradient(to right, #f8f9fa, #ffffff); }
.custom-checkbox-modern .form-check-input { width: 1.1em; height: 1.1em; }
.custom-checkbox-modern .form-check-label { padding-top: 2px; }
</style>
@endsection

@section('js')
<script>
$(function() {
    // Select/Deselect All
    $('#selectAll').on('click', function() {
        $('.permission-checkbox').prop('checked', true);
        $('.group-check').prop('checked', true);
    });
    $('#deselectAll').on('click', function() {
        $('.permission-checkbox').prop('checked', false);
        $('.group-check').prop('checked', false);
    });

    // Group Select
    $('.group-check').on('change', function() {
        var group = $(this).data('group');
        $('.permission-checkbox[data-group="' + group + '"]').prop('checked', $(this).is(':checked'));
    });

    // Update group checkboxes on load
    $('.group-check').each(function() {
        var group = $(this).data('group');
        var allChecked = $('.permission-checkbox[data-group="' + group + '"]:not(:checked)').length === 0;
        $(this).prop('checked', allChecked);
    });

    // Form submission
    $('#editRoleForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Updated!',
                        text: response.message,
                        icon: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-primary px-5'
                    }).then(() => {
                        window.location.href = "{{ route('admin.roles.index') }}";
                    });
                }
            },
            error: function(xhr) {
                var errorMsg = 'An unexpected error occurred.';
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    errorMsg = '';
                    $.each(errors, function(key, value) {
                        errorMsg += value + '<br>';
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });
});
</script>
@endsection
