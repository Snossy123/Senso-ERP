@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Income Statement</h2>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-3">
                <label>Start</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label>End</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary" type="submit">Run</button>
            </div>
        </form>
        <h5>Revenue</h5>
        <table class="table table-sm mb-4">
            @foreach($data['revenues'] as $row)
            <tr><td>{{ $row['name'] }}</td><td class="text-right">{{ number_format($row['balance'], 2) }}</td></tr>
            @endforeach
            <tr class="font-weight-bold"><td>Total Revenue</td><td class="text-right">{{ number_format($data['total_revenue'], 2) }}</td></tr>
        </table>
        <h5>Expenses</h5>
        <table class="table table-sm mb-4">
            @foreach($data['expenses'] as $row)
            <tr><td>{{ $row['name'] }}</td><td class="text-right">{{ number_format($row['balance'], 2) }}</td></tr>
            @endforeach
            <tr class="font-weight-bold"><td>Total Expenses</td><td class="text-right">{{ number_format($data['total_expense'], 2) }}</td></tr>
        </table>
        <div class="alert alert-primary">Net Income: <strong>{{ number_format($data['net_income'], 2) }}</strong></div>
    </div>
</div>
@endsection
