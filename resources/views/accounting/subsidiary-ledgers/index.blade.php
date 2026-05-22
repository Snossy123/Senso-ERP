@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Accounts Receivable & Payable</h2>
        <p class="mg-b-0 text-muted">Open documents vs control account GL balances ({{ $baseCurrency ?? 'USD' }}).</p>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Accounts Receivable</h5>
                    <p class="text-muted mb-0 small">GL control: <strong>{{ number_format($ar['gl_balance'], 2) }}</strong> | Open docs: <strong>{{ number_format($ar['open_document_total'], 2) }}</strong></p>
                </div>
                <a href="{{ route('accounting.customer-receipts') }}" class="btn btn-sm btn-success">Collect</a>
            </div>
            <div class="card-body">
                <h6 class="text-muted">AR aging (open balances)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead>
                        <tr>
                            <th>Current</th>
                            <th>1–30</th>
                            <th>31–60</th>
                            <th>61–90</th>
                            <th>90+</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ number_format($ar['aging']['current'] ?? 0, 2) }}</td>
                            <td>{{ number_format($ar['aging']['1_30'] ?? 0, 2) }}</td>
                            <td>{{ number_format($ar['aging']['31_60'] ?? 0, 2) }}</td>
                            <td>{{ number_format($ar['aging']['61_90'] ?? 0, 2) }}</td>
                            <td>{{ number_format($ar['aging']['over_90'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Customer</th><th>Docs</th><th class="text-right">Balance</th></tr></thead>
                    <tbody>
                        @forelse($ar['customers'] as $row)
                        <tr>
                            <td>{{ $row['customer_name'] }}</td>
                            <td>{{ $row['document_count'] }}</td>
                            <td class="text-right">{{ number_format($row['open_balance'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No open receivables.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5>Accounts Payable</h5>
                <p class="text-muted mb-0 small">GL control: <strong>{{ number_format($ap['gl_balance'], 2) }}</strong> | Received POs (unpaid): <strong>{{ number_format($ap['open_document_total'], 2) }}</strong></p>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Supplier</th><th>POs</th><th class="text-right">Balance</th></tr></thead>
                    <tbody>
                        @forelse($ap['suppliers'] as $row)
                        <tr>
                            <td>{{ $row['supplier_name'] }}</td>
                            <td>{{ $row['document_count'] }}</td>
                            <td class="text-right">{{ number_format($row['open_balance'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No open payables.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<p class="text-muted tx-12 mt-3">
    <a href="{{ route('accounting.disbursements') }}">Cash disbursements</a> ·
    <a href="{{ route('accounting.customer-receipts') }}">Customer receipts</a> ·
    <a href="{{ route('accounting.bank-reconciliation') }}">Bank reconciliation</a>
</p>
@endsection
