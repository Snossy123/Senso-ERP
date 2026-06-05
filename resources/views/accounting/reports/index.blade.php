@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Financial Reports</h2>
            <p class="mg-b-0">Period-based trial balance, P&amp;L, balance sheet, and general ledger.</p>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row row-sm">
    <div class="col-xl-3 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="mb-2">Trial Balance</h4>
                <p class="mb-3 text-muted">Verify debits equal credits as of a date.</p>
                <a href="{{ route('accounting.reports.trial-balance') }}" class="btn btn-warning">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="mb-2">Income Statement</h4>
                <p class="mb-3 text-muted">Revenue and expenses for a date range.</p>
                <a href="{{ route('accounting.reports.income-statement') }}" class="btn btn-primary">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="mb-2">Balance Sheet</h4>
                <p class="mb-3 text-muted">Assets, liabilities, and equity snapshot.</p>
                <a href="{{ route('accounting.reports.balance-sheet') }}" class="btn btn-success">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="mb-2">General Ledger</h4>
                <p class="mb-3 text-muted">Account-level transaction detail.</p>
                <a href="{{ route('accounting.reports.general-ledger') }}" class="btn btn-info">View Report</a>
            </div>
        </div>
    </div>
</div>
@endsection
