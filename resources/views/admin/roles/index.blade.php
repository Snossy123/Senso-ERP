@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">{{ __('messages.common.admin') }}</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ {{ __('roles.breadcrumb_roles') }}</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> {{ __('roles.add_new_role') }}</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">{{ __('roles.title_list') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>{{ __('roles.name') }}</th>
                                <th>{{ __('roles.slug') }}</th>
                                <th>{{ __('roles.description') }}</th>
                                <th class="text-center">{{ __('roles.permissions_count') }}</th>
                                <th class="text-center">{{ __('roles.users_count') }}</th>
                                <th>{{ __('roles.status') }}</th>
                                <th>{{ __('roles.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td class="tx-12 text-muted">{{ $role->description ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-info-light">{{ $role->permissions_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary-light">{{ $role->users_count }}</span>
                                </td>
                                <td>
                                    @if($role->is_active)
                                        <span class="badge badge-success">{{ __('messages.common.active') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('messages.common.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary-light"><i class="fa fa-edit"></i></a>
                                    @if(!in_array($role->slug, ['admin', 'manager']))
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline delete-role-form" data-name="{{ $role->name }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger-light"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted">{{ __('roles.no_roles') }}</p>
                                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">{{ __('roles.create_first') }}</a>
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

@section('js')
<script>
$(function() {
    $('.delete-role-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var name = form.data('name');

        if (confirm(@json(__('roles.delete_confirm')).replace(':name', name))) {
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        form.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.error : @json(__('roles.delete_error'));
                    alert(msg);
                }
            });
        }
    });
});
</script>
@endsection
