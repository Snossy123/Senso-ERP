@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Customer Receipts</h2>
        <p class="mg-b-0 text-muted">Collect cash for web orders and credit POS sales — clears AR or recognizes revenue on payment.</p>
    </div>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5>Web orders awaiting payment</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th class="text-right">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collectibleOrders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ str_replace('_', ' ', $order->payment_method) }}</td>
                            <td class="text-right">{{ number_format($order->total, 2) }}</td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#collectOrder{{ $order->id }}">Collect</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No unpaid web orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5>Credit POS sales (AR)</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Sale</th>
                            <th>Customer</th>
                            <th class="text-right">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditSales as $sale)
                        <tr>
                            <td>{{ $sale->sale_number }}</td>
                            <td>{{ $sale->customer_name ?? $sale->customer?->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($sale->total, 2) }}</td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#collectSale{{ $sale->id }}">Collect</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No open credit sales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5>Recent receipts</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Document</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @forelse($recentReceipts as $receipt)
                        <tr>
                            <td>{{ $receipt->payment_number }}</td>
                            <td>{{ $receipt->order?->order_number ?? $receipt->sale?->sale_number ?? '—' }}</td>
                            <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No receipts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($collectibleOrders as $order)
<div class="modal fade" id="collectOrder{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('accounting.customer-receipts.collect', $order) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Collect — {{ $order->order_number }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Customer: <strong>{{ $order->customer_name }}</strong></p>
                <p>Amount: <strong>{{ number_format($order->total, 2) }}</strong></p>
                <div class="form-group">
                    <label>Receipt date</label>
                    <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Collection method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Record receipt</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@foreach($creditSales as $sale)
<div class="modal fade" id="collectSale{{ $sale->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('accounting.customer-receipts.collect-sale', $sale) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Collect — {{ $sale->sale_number }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Customer: <strong>{{ $sale->customer_name ?? $sale->customer?->name }}</strong></p>
                <p>Amount: <strong>{{ number_format($sale->total, 2) }}</strong></p>
                <div class="form-group">
                    <label>Receipt date</label>
                    <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Collection method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Record receipt</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
