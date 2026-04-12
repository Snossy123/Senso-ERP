@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Accounting Settings</h2>
            <p class="mg-b-0">Map operational flows to General Ledger accounts.</p>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header pb-1">
                <h3 class="card-title mb-2">Operational Mappings</h3>
                <p class="text-muted">These settings determine which accounts are used for automated journal entries in POS and Inventory.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('accounting.settings.update') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Operation / Action</th>
                                    <th>GL Account</th>
                                    <th>Mapping Key (Internal)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappingKeys as $key => $label)
                                <tr>
                                    <td class="align-middle"><strong>{{ $label }}</strong></td>
                                    <td>
                                        <select name="mappings[{{ $key }}]" class="form-control select2">
                                            <option value="">-- Select Account --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ ($settings[$key] ?? '') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->code }} - {{ $account->name }} ({{ ucfirst($account->type) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="align-middle text-muted"><small>{{ $key }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Mappings</button>
                        <a href="{{ route('accounting.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
