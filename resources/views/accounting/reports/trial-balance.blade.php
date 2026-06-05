@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Trial Balance</h2>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <label>As of date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary" type="submit">Run</button>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['accounts'] as $row)
                <tr>
                    <td>{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    <td>{{ ucfirst($row['type']) }}</td>
                    <td class="text-right">{{ number_format($row['debit'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['credit'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
                    <td colspan="3">Totals</td>
                    <td class="text-right">{{ number_format($data['total_debit'], 2) }}</td>
                    <td class="text-right">{{ number_format($data['total_credit'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @if($data['is_balanced'])
            <div class="alert alert-success">Trial balance is balanced.</div>
        @else
            <div class="alert alert-danger">Trial balance is out of balance.</div>
        @endif
    </div>
</div>
@endsection
