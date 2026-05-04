@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">{{ __('messages.common.admin') }}</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ {{ __('roles.breadcrumb_add') }}</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header pb-0 mt-2">
                <h4 class="card-title">{{ __('roles.create_new_role') }}</h4>
            </div>
            <div class="card-body">
                <form id="createRoleForm" action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('roles.role_name_field') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required placeholder="{{ __('roles.role_name_placeholder') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('roles.description') }}</label>
                                <textarea name="description" id="description" class="form-control" rows="1" placeholder="{{ __('roles.description_placeholder') }}"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="custom-control custom-checkbox pt-2">
                            <input type="checkbox" class="custom-control-input" name="is_active" value="1" checked>
                            <span class="custom-control-label">{{ __('roles.active_role') }}</span>
                        </label>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">{{ __('roles.assign_permissions') }}</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" id="selectAll">{{ __('roles.select_all') }}</button>
                            <button type="button" class="btn btn-outline-secondary" id="deselectAll">{{ __('roles.deselect_all') }}</button>
                        </div>
                    </div>

                    @if(!empty($permissionsGrouped))
                        <div class="row">
                        @foreach($permissionsGrouped as $groupKey => $group)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-light border shadow-none">
                                    <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                                        <strong>{{ $group['name'] ?? ucfirst($groupKey) }}</strong>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input group-check" data-group="{{ $groupKey }}" id="group_{{ $groupKey }}">
                                            <label class="custom-control-label" for="group_{{ $groupKey }}"></label>
                                        </div>
                                    </div>
                                    <div class="card-body py-2">
                                        @foreach($group['permissions'] as $permission)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-group="{{ $groupKey }}" id="perm_{{ $permission->id }}">
                                                <label class="form-check-label tx-12" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @endif

                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary px-5 btn-lg">{{ __('roles.create_submit') }}</button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-lg">{{ __('roles.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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

    // Form submission
    $('#createRoleForm').on('submit', function(e) {
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
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = "{{ route('admin.roles.index') }}";
                    });
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMsg = '';
                $.each(errors, function(key, value) {
                    errorMsg += value + '<br>';
                });
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });
});
</script>
@endsection
