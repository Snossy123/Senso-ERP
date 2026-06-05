@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Opening Balances</h2>
        <p class="mg-b-0 text-muted">Post a balanced opening entry (total debits must equal total credits).</p>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('accounting.opening-balance.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Go-live date</label>
                    <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-8 form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" value="Opening balances">
                </div>
            </div>
            <table class="table table-bordered" id="linesTable">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 4; $i++)
                    <tr>
                        <td>
                            <select name="lines[{{ $i }}][account_id]" class="form-control" required>
                                <option value="">Select account</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="form-control" value="0"></td>
                        <td><input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="form-control" value="0"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Post Opening Balances</button>
        </form>
    </div>
</div>
@endsection
