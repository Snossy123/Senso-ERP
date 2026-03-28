@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Sales History</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('pos.terminal') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New Sale</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Sales Transactions</h4>
                </div>
                <p class="tx-12 tx-gray-500 mb-2">All POS transactions history.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Cashier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td><strong>{{ $sale->sale_number }}</strong></td>
                                <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td><span class="badge badge-light">{{ $sale->items->count() }}</span></td>
                                <td>
                                    @if($sale->payment_method === 'cash')
                                        <span class="badge badge-success">Cash</span>
                                    @elseif($sale->payment_method === 'card')
                                        <span class="badge badge-info">Card</span>
                                    @else
                                        <span class="badge badge-warning">Bank Transfer</span>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-primary">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</td>
                                <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-info-light"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fe fe-shopping-cart tx-40 text-muted"></i>
                                    <p class="text-muted mt-2">No sales recorded yet</p>
                                    <a href="{{ route('pos.terminal') }}" class="btn btn-primary">Start First Sale</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $sales->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
