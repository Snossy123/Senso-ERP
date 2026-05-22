@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Balance Sheet</h2>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <label>As of date</label>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary" type="submit">Run</button>
            </div>
        </form>
        <div class="row">
            <div class="col-md-4">
                <h5>Assets</h5>
                @foreach($data['assets'] as $row)
                <div class="d-flex justify-content-between"><span>{{ $row['name'] }}</span><span>{{ number_format($row['balance'], 2) }}</span></div>
                @endforeach
                <p class="font-weight-bold mt-2">Total: {{ number_format($data['total_assets'], 2) }}</p>
            </div>
            <div class="col-md-4">
                <h5>Liabilities</h5>
                @foreach($data['liabilities'] as $row)
                <div class="d-flex justify-content-between"><span>{{ $row['name'] }}</span><span>{{ number_format($row['balance'], 2) }}</span></div>
                @endforeach
                <p class="font-weight-bold mt-2">Total: {{ number_format($data['total_liabilities'], 2) }}</p>
            </div>
            <div class="col-md-4">
                <h5>Equity</h5>
                @foreach($data['equities'] as $row)
                <div class="d-flex justify-content-between"><span>{{ $row['name'] }}</span><span>{{ number_format($row['balance'], 2) }}</span></div>
                @endforeach
                <p class="font-weight-bold mt-2">Total: {{ number_format($data['total_equity'], 2) }}</p>
            </div>
        </div>
        @if($data['is_balanced'])
            <div class="alert alert-success mt-3">Balance sheet equation holds (Assets = Liabilities + Equity).</div>
        @else
            <div class="alert alert-warning mt-3">Balance sheet is out of balance for the selected date.</div>
        @endif
    </div>
</div>
@endsection
