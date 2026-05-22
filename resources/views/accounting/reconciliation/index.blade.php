@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Accounting Reconciliation</h2>
        <p class="mg-b-0 text-muted">Documents that should have a journal entry but do not.</p>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($missing->isEmpty())
            <div class="alert alert-success">All checked documents have matching journal entries.</div>
        @else
            <div class="alert alert-warning">{{ $missing->count() }} document(s) missing journal entries. Run <code>php artisan accounting:reconcile</code> nightly for log alerts.</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Document ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($missing as $row)
                    <tr>
                        <td>{{ $row['document_type'] }}</td>
                        <td>{{ $row['reference'] }}</td>
                        <td>{{ $row['document_id'] }}</td>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
