@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Cash Disbursements</h2>
        <p class="mg-b-0 text-muted">Pay suppliers for received purchase orders (Dr AP / Cr Cash or Bank).</p>
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
        <div class="card">
            <div class="card-header"><h5>Payable purchase orders</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Received</th>
                            <th class="text-right">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payableOrders as $order)
                        <tr>
                            <td><a href="{{ route('inventory.purchase-orders.show', $order) }}">{{ $order->reference_no }}</a></td>
                            <td>{{ $order->supplier?->name }}</td>
                            <td>{{ $order->received_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-right">{{ number_format($order->total_amount, 2) }}</td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#payModal{{ $order->id }}">Pay</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No unpaid received orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5>Recent payments</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>PO</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->purchaseOrder?->reference_no }}</td>
                            <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($payableOrders as $order)
<div class="modal fade" id="payModal{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('accounting.disbursements.pay', $order) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Pay {{ $order->reference_no }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Supplier: <strong>{{ $order->supplier?->name }}</strong></p>
                <p>Amount: <strong>{{ number_format($order->total_amount, 2) }}</strong></p>
                <div class="form-group">
                    <label>Payment date</label>
                    <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Record payment</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
