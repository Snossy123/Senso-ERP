@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Purchase Order Detail</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        @if($order->status === 'draft' || $order->status === 'ordered')
            <button type="button" class="btn btn-primary-light mr-2" onclick="$('#receiveModal').modal('show')">
                <i class="fa fa-download"></i> Receive Stock
            </button>
        @endif
        <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Order Information</h4>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>PO Reference:</strong> <span class="text-primary">{{ $order->reference_no }}</span></p>
                <p class="mb-2"><strong>Supplier:</strong> {{ $order->supplier->name }}</p>
                <p class="mb-2"><strong>Ship To:</strong> {{ $order->warehouse->name }}</p>
                <p class="mb-2"><strong>Order Date:</strong> {{ $order->order_date->format('Y-m-d') }}</p>
                <p class="mb-2"><strong>Status:</strong> 
                    <span class="badge @if($order->status === 'received') badge-success @elseif($order->status === 'draft') badge-light @elseif($order->status === 'ordered') badge-primary @else badge-info @endif">
                        {{ strtoupper($order->status) }}
                    </span>
                </p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="tx-15 font-weight-bold">Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>{{ config('app.currency') }} {{ number_format($order->total_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mt-3 font-weight-bold border-top pt-2 tx-18">
                    <span>Grand Total</span>
                    <span class="text-primary">{{ config('app.currency') }} {{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Order Items</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product / Variant</th>
                                <th class="text-center">Order Qty</th>
                                <th class="text-right">Unit Cost</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    {{ $item->product->name }}
                                    @if($item->variant)
                                        <br><small class="text-muted">Variant: {{ $item->variant->name }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">{{ config('app.currency') }} {{ number_format($item->unit_cost, 2) }}</td>
                                <td class="text-right">{{ config('app.currency') }} {{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receive Modal Placeholder -->
<div class="modal fade" id="receiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receive Purchase Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('inventory.purchase-orders.receive', $order) }}" method="POST">
                @csrf
                <div class="modal-body text-center">
                    <p class="tx-16">Are you sure you want to receive this order?</p>
                    <p class="text-muted">This will increment stock quantities in <br><strong>{{ $order->warehouse->name }}</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-5">Confirm Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
