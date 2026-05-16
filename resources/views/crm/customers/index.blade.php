@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">CRM</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Customers</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('crm.customers.create') }}" class="btn btn-primary"><i class="fe fe-user-plus"></i> Add customer</a>
        <a href="{{ route('exports.customers.excel') }}" class="btn btn-outline-secondary ml-2"><i class="fe fe-download"></i> Export</a>
    </div>
</div>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form method="get" class="form-row mb-4">
            <div class="col-md-5">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, email, phone, company">
            </div>
            <div class="col-md-3">
                <select name="tag_id" class="form-control">
                    <option value="">All tags</option>
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(request('tag_id') == $tag->id)>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-block">Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Tags</th>
                        <th>Assigned</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td class="font-weight-semibold">
                                <a href="{{ route('crm.customers.show', $customer) }}">{{ $customer->name }}</a>
                            </td>
                            <td>{{ $customer->company ?? '—' }}</td>
                            <td class="tx-13">
                                {{ $customer->phone }}<br>
                                <span class="text-muted">{{ $customer->email }}</span>
                            </td>
                            <td>
                                @foreach($customer->tags as $tag)
                                    <span class="badge" style="background:{{ $tag->color }}20;color:{{ $tag->color }}">{{ $tag->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $customer->assignedUser?->name ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('crm.customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $customers->links() }}
    </div>
</div>
@endsection
