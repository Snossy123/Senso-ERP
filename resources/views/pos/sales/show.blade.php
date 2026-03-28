@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Sale #{{ $sale->sale_number }}</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('pos.sales.index') }}" class="btn btn-secondary mr-2">Back to List</a>
        <a href="{{ route('exports.receipt.pdf', $sale) }}" class="btn btn-info mr-2"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print</button>
    </div>
</div>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card" id="receipt">
            <div class="card-header text-center">
                <h4 class="mb-0">Senso ERP</h4>
                <p class="mb-0 tx-12 text-muted">Point of Sale Receipt</p>
            </div>
            <div class="card-body">
                <div class="text-center border-bottom pb-3 mb-3">
                    <p class="mb-1"><strong>Receipt #:</strong> {{ $sale->sale_number }}</p>
                    <p class="mb-1"><strong>Date:</strong> {{ $sale->created_at->format('M d, Y H:i') }}</p>
                    <p class="mb-0"><strong>Cashier:</strong> {{ $sale->user?->name ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Customer:</strong> {{ $sale->customer?->name ?? 'Walk-in Customer' }}</p>
                </div>

                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td>
                                {{ $item->product?->name ?? 'Product Deleted' }}
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ config('app.currency') }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ config('app.currency') }} {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-top mt-3 pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>{{ config('app.currency') }} {{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if($sale->discount_amount > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Discount</span>
                        <span>-{{ config('app.currency') }} {{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($sale->tax_amount > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax</span>
                        <span>{{ config('app.currency') }} {{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between font-weight-bold tx-18 border-top pt-2">
                        <span>TOTAL</span>
                        <span class="text-primary">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</span>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="mb-1"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</p>
                    <p class="mb-0"><strong>Status:</strong> 
                        @if($sale->payment_status === 'paid')
                            <span class="badge badge-success">PAID</span>
                        @else
                            <span class="badge badge-warning">{{ strtoupper($sale->payment_status) }}</span>
                        @endif
                    </p>
                </div>

                @if($sale->notes)
                <div class="mt-3 border-top pt-3">
                    <p class="mb-0"><strong>Notes:</strong> {{ $sale->notes }}</p>
                </div>
                @endif
            </div>
            <div class="card-footer text-center">
                <p class="mb-0 tx-12 text-muted">Thank you for your purchase!</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
@media print {
    .breadcrumb-header, .card-header, .btn, .breadcrumb, footer { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
