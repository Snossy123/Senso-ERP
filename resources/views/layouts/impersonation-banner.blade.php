@if(session('platform_operator_id') && auth()->user()?->tenant_id)
@php
    $impersonatedTenant = \App\Models\Tenant::find(session('admin_logged_in_as_tenant'));
@endphp
<div class="alert alert-warning mb-0 rounded-0 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
    <span>
        <i class="fe fe-alert-triangle me-1"></i>
        {{ __('platform.impersonation.banner', ['tenant' => $impersonatedTenant?->name ?? auth()->user()->tenant?->name ?? '—']) }}
    </span>
    <form action="{{ route('platform.impersonation.stop') }}" method="POST" class="mb-0">
        @csrf
        <button type="submit" class="btn btn-sm btn-dark">
            <i class="fe fe-log-out"></i> {{ __('platform.impersonation.stop') }}
        </button>
    </form>
</div>
@endif
