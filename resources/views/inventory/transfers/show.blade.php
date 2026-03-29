@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Logistics</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Transfer Detail</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Transfer Overview</h4>
            </div>
            <div class="card-body">
                <p class="mb-3"><strong>Reference #:</strong> <span class="text-info">{{ $transfer->reference_no }}</span></p>
                <div class="row text-center mb-4">
                    <div class="col-5">
                        <p class="mb-1 text-muted">From</p>
                        <h5 class="tx-16 font-weight-bold">{{ $transfer->fromWarehouse->name }}</h5>
                    </div>
                    <div class="col-2 align-self-center mt-2">
                        <i class="fa fa-arrow-right text-muted tx-20"></i>
                    </div>
                    <div class="col-5">
                        <p class="mb-1 text-muted">To</p>
                        <h5 class="tx-16 font-weight-bold text-primary">{{ $transfer->toWarehouse->name }}</h5>
                    </div>
                </div>
                <p class="mb-2"><strong>Date:</strong> {{ $transfer->transfer_date->format('Y-m-d') }}</p>
                <p class="mb-2"><strong>Status:</strong> <span class="badge badge-success">COMPLETED</span></p>
                <p class="mb-2"><strong>Created By:</strong> {{ $transfer->creator->name ?? 'System' }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <h4 class="card-title">Transferred Items</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product / Variant</th>
                                <th class="text-center">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                            <tr>
                                <td>
                                    {{ $item->product->name }}
                                    @if($item->variant)
                                        <br><small class="text-muted">Variant: {{ $item->variant->name }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
