@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Stock Transfers</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <div class="pr-1 mb-3 mb-xl-0">
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary"><i class="fa fa-exchange-alt"></i> New Transfer</a>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Stock Transfers</h4>
                <p class="tx-12 tx-gray-500 mb-2">Move inventory between warehouses.</p>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Transfer #</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                            <tr>
                                <td><strong>{{ $transfer->reference_no }}</strong></td>
                                <td>{{ $transfer->fromWarehouse->name }}</td>
                                <td>{{ $transfer->toWarehouse->name }}</td>
                                <td>{{ $transfer->transfer_date->format('Y-m-d') }}</td>
                                <td>{{ $transfer->items->count() }} line items</td>
                                <td>
                                    <span class="badge badge-success">COMPLETED</span>
                                </td>
                                <td>
                                    <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-sm btn-info-light"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted">No stock transfers found</p>
                                    <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">Start First Transfer</a>
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
