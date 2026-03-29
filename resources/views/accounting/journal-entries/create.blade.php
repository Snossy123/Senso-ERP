@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Create Journal Entry</h2>
    </div>
</div>
@endsection

@section('content')
<div class="row row-sm">
    <div class="col-xl-12 col-md-12 col-lg-12">
        <div class="card">
            <form action="{{ route('accounting.journal-entries.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="Optional reference">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" required placeholder="Description of the entry">
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3">Line Items</h4>
                    <table class="table table-bordered" id="linesTable">
                        <thead>
                            <tr>
                                <th>Account *</th>
                                <th>Debit *</th>
                                <th>Credit *</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="lines[0][account_id]" class="form-control select2" required>
                                        @foreach(\App\Models\Account::where('parent_id', '!=', null)->get() as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="lines[0][debit]" class="form-control debit-input" value="0.00" required></td>
                                <td><input type="number" step="0.01" name="lines[0][credit]" class="form-control credit-input" value="0.00" required></td>
                                <td><input type="text" name="lines[0][description]" class="form-control" placeholder="Line description"></td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <select name="lines[1][account_id]" class="form-control select2" required>
                                        @foreach(\App\Models\Account::where('parent_id', '!=', null)->get() as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="lines[1][debit]" class="form-control debit-input" value="0.00" required></td>
                                <td><input type="number" step="0.01" name="lines[1][credit]" class="form-control credit-input" value="0.00" required></td>
                                <td><input type="text" name="lines[1][description]" class="form-control" placeholder="Line description"></td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-info mt-2" onclick="addLine()">+ Add Line</button>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6 text-right">
                            <h4 class="text-secondary">Totals</h4>
                            <p>Total Debit: <span id="total_debit">0.00</span></p>
                            <p>Total Credit: <span id="total_credit">0.00</span></p>
                            <p id="balance_status" class="text-success font-weight-bold">Balanced</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success" id="submitBtn">Post Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let lineCount = 2;
    function addLine() {
        const row = `
            <tr>
                <td>
                    <select name="lines[${lineCount}][account_id]" class="form-control select2" required>
                        @foreach(\App\Models\Account::where('parent_id', '!=', null)->get() as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][debit]" class="form-control debit-input" value="0.00" required></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][credit]" class="form-control credit-input" value="0.00" required></td>
                <td><input type="text" name="lines[${lineCount}][description]" class="form-control" placeholder="Line description"></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); calculateTotals();">X</button></td>
            </tr>
        `;
        document.querySelector('#linesTable tbody').insertAdjacentHTML('beforeend', row);
        lineCount++;
        bindCalculation();
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(input => {
            totalDebit += parseFloat(input.value || 0);
        });

        document.querySelectorAll('.credit-input').forEach(input => {
            totalCredit += parseFloat(input.value || 0);
        });

        document.getElementById('total_debit').innerText = totalDebit.toFixed(2);
        document.getElementById('total_credit').innerText = totalCredit.toFixed(2);

        const btn = document.getElementById('submitBtn');
        const status = document.getElementById('balance_status');

        if (Math.abs(totalDebit - totalCredit) > 0.001 || totalDebit <= 0) {
            status.innerText = "Unbalanced";
            status.className = "text-danger font-weight-bold";
            btn.disabled = true;
        } else {
            status.innerText = "Balanced";
            status.className = "text-success font-weight-bold";
            btn.disabled = false;
        }
    }

    function bindCalculation() {
        document.querySelectorAll('.debit-input, .credit-input').forEach(input => {
            input.addEventListener('input', calculateTotals);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindCalculation();
        calculateTotals();
    });
</script>
@endsection
