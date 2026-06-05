@extends('layouts.master')

@section('page-header')
<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Journal Entries</h2>
            <p class="mg-b-0">Manage accounting ledgers and transactions.</p>
        </div>
    </div>
    <div class="main-dashboard-header-right">
        <div>
            <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-primary">
                <i class="fe fe-plus"></i> New Journal Entry
            </a>
        </div>
    </div>
</div>
<!-- /breadcrumb -->
@endsection

@section('content')
<!-- row -->
<div class="row row-sm">
    <div class="col-xl-12 col-md-12 col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 text-nowrap">
                        <thead class="bg-light">
                            <tr>
                                <th># Reference</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Source</th>
                                <th>Debits</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                            <tr>
                                <td>{{ $entry->reference }}</td>
                                <td>{{ $entry->date->format('Y-m-d') }}</td>
                                <td>{{ $entry->description }}</td>
                                <td>
                                    @if($entry->source_type)
                                        <span class="badge badge-secondary">{{ class_basename($entry->source_type) }} #{{ $entry->source_id }}</span>
                                    @else
                                        <span class="badge badge-light">Manual Entry</span>
                                    @endif
                                </td>
                                <td><span class="text-success">${{ number_format($entry->total_debit, 2) }}</span></td>
                                <td><span class="text-danger">${{ number_format($entry->total_credit, 2) }}</span></td>
                                <td>
                                    @php
                                        $badge = match($entry->status) {
                                            'posted' => 'success',
                                            'approved' => 'info',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ ucfirst($entry->status) }}</span>
                                </td>
                                <td>
                                    @if(($canApprove ?? false) && $entry->status === 'draft')
                                    <form action="{{ route('accounting.journal-entries.approve', $entry) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-primary">Approve</button>
                                    </form>
                                    @endif
                                    @if(($canPost ?? false) && in_array($entry->status, ['draft', 'approved']))
                                    <form action="{{ route('accounting.journal-entries.post', $entry) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success">Post</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No journal entries found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $entries->links() }}
            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
