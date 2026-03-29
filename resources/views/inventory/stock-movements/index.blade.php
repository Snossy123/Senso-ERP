@extends('layouts.master')

@section('title', 'Stock Movements')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Stock Movement History</h3>
                <a href="{{ route('inventory.movements.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus mr-1"></i> New Movement
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Before</th>
                                <th>After</th>
                                <th>Unit Cost</th>
                                <th>Value</th>
                                <th>Ref</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('M d, H:i') }}</td>
                                <td>
                                    <strong>{{ $movement->warehouse?->name ?? 'Global' }}</strong>
                                </td>
                                <td>
                                    {{ $movement->product->name }}
                                    @if($movement->variant)
                                        <br><small class="text-muted">{{ $movement->variant->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($movement->type === 'in')
                                        <span class="badge badge-success">IN</span>
                                    @elseif($movement->type === 'out')
                                        <span class="badge badge-danger">OUT</span>
                                    @else
                                        <span class="badge badge-info">ADJ</span>
                                    @endif
                                </td>
                                <td>{{ $movement->quantity }}</td>
                                <td>{{ $movement->before_quantity }}</td>
                                <td>{{ $movement->after_quantity }}</td>
                                <td>{{ number_format($movement->unit_cost, 2) }}</td>
                                <td>{{ number_format($movement->total_value, 2) }}</td>
                                <td>
                                    <small>{{ $movement->reference ?? '---' }}</small>
                                    @if($movement->purchase_order_id)
                                        <br><span class="badge badge-secondary p-1">PO</span>
                                    @elseif($movement->stock_transfer_id)
                                        <br><span class="badge badge-secondary p-1">TR</span>
                                    @endif
                                </td>
                                <td>{{ $movement->user->name ?? '---' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No movements recorded.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
