@extends('layouts.master')

@section('page-header')
<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Financial Reports</h2>
            <p class="mg-b-0">Get deep insights into your bottom-line accounting.</p>
        </div>
    </div>
</div>
<!-- /breadcrumb -->
@endsection

@section('content')
<!-- row -->
<div class="row row-sm">
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <div class="feature-widget">
                    <div class="mb-3">
                        <i class="fe fe-trending-up tx-40 text-primary"></i>
                    </div>
                    <h4 class="mb-2">Income Statement (P&L)</h4>
                    <p class="mb-3">Analyze revenue and expenses over a specific period.</p>
                    <a href="#" class="btn btn-primary" onclick="alert('This report is fetched via the API directly or can be built into a Vue/React module!')">View Report</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <div class="feature-widget">
                    <div class="mb-3">
                        <i class="fe fe-layers tx-40 text-success"></i>
                    </div>
                    <h4 class="mb-2">Balance Sheet</h4>
                    <p class="mb-3">A snapshot of assets, liabilities, and equity at a given point.</p>
                    <a href="#" class="btn btn-success" onclick="alert('This report is fetched via the API directly or can be built into a Vue/React module!')">View Report</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card text-center">
            <div class="card-body">
                <div class="feature-widget">
                    <div class="mb-3">
                        <i class="fe fe-check-square tx-40 text-warning"></i>
                    </div>
                    <h4 class="mb-2">Trial Balance</h4>
                    <p class="mb-3">Verify that total debits equal total credits for all accounts.</p>
                    <a href="#" class="btn btn-warning" onclick="alert('This report is fetched via the API directly or can be built into a Vue/React module!')">View Report</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
