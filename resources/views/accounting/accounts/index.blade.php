@extends('layouts.master')

@section('page-header')
<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Chart of Accounts</h2>
            <p class="mg-b-0">Manage accounting ledgers and tree structure.</p>
        </div>
    </div>
    <div class="main-dashboard-header-right">
        <div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addAccountModal">
                <i class="fe fe-plus"></i> Add Account
            </button>
        </div>
    </div>
</div>
<!-- /breadcrumb -->
@endsection

@section('content')
<!-- row -->
<div class="row row-sm">
    <div class="col-xl-12 col-md-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <ul class="list-group">
                    @forelse($accounts as $account)
                        @include('accounting.accounts.partials.tree', ['account' => $account])
                    @empty
                        <li class="list-group-item">No accounts found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- row closed -->

<!-- Add Account Modal Placeholder -->
<div class="modal fade" id="addAccountModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">New Account</h6>
                <button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('accounting.accounts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Account Code</label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. 1010">
                    </div>
                    <div class="form-group">
                        <label>Account Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Cash">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control" required>
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parent Account</label>
                        <select name="parent_id" class="form-control">
                            <option value="">None (Top Level)</option>
                            @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn ripple btn-primary" type="submit">Save changes</button>
                    <button class="btn ripple btn-secondary" data-dismiss="modal" type="button">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
