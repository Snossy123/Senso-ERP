@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between flex-wrap gap-2">
    <div class="my-auto">
        <div class="d-flex flex-wrap">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Shift reports</span>
        </div>
        <small class="text-muted d-block mt-1">Liquidity stewardship · reconcile before bank deposits</small>
    </div>
</div>
@endsection

@section('css')
<style>
    .shift-hero { border-radius: 18px; padding:18px 20px;background:linear-gradient(135deg,#0f172a,#312e81);color:#fff;box-shadow:0 22px 50px rgba(15,23,42,0.25);margin-bottom:1rem;border:1px solid rgba(255,255,255,0.08);}
    .shift-filter-pane {border-radius:16px;background:rgba(247,249,254,0.95);border:1px solid #e5e9f8;padding:16px;margin-bottom:1rem;backdrop-filter:saturate(160%) blur(12px);}
    .shift-kpi-mini {border-radius:16px;background:#fff;border:1px solid #eaeef9;padding:16px;margin-bottom:.75rem;box-shadow:0 10px 28px rgba(15,23,42,0.06);}
    .chip-var{font-weight:800;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;padding:.35rem .75rem;border-radius:999px;}
    .chip-var.ok{background:#daf5e9;color:#0f5132;}
    .chip-var.bad{background:#fee2e2;color:#991b1b;}
</style>
@endsection

@section('content')
<div class="row mx-2">
    <div class="col-xl-4">
        @php $openOnPage = $shifts->getCollection()->where('status','open')->count(); @endphp
        <div class="shift-hero mb-4 h-100">
            <small class="text-uppercase tx-11 opacity-70 d-block mb-4">Operational pulse · this listing</small>
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="h1 font-weight-bolder mb-1">{{ $openOnPage }}</div>
                    <div class="tx-13 opacity-80">Lanes currently open inside this page snapshot</div>
                </div>
                <span class="badge badge-light text-dark rounded-pill px-3">{{ $shifts->total() }} records</span>
            </div>
            <p class="small opacity-85 mb-0 mt-5">Variance highlights flag coaching moments before escalation.</p>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="row row-sm mb-4">
            <div class="col-md-6">
                <div class="shift-kpi-mini h-100">
                    <small class="text-uppercase text-muted font-weight-bold">Filters</small>
                    <div class="h5 mb-3 font-weight-semibold mt-2">Sticky controls</div>
                    <small class="text-muted">Pinned while scrolling on desktop for faster audits.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="shift-kpi-mini h-100">
                    <small class="text-uppercase text-muted font-weight-bold">Phase 3</small>
                    <div class="h5 mb-3 font-weight-semibold mt-2">Realtime overlays</div>
                    <small class="text-muted">Upcoming websocket drawer sync replaces manual snapshots.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mx-2">
    <div class="col-xl-12">
        <div class="card border-0 shadow-lg rounded-xl">
            <div class="card-body pb-4">
                <div class="shift-filter-pane sticky-top mt-2 mt-md-0">
                    <form action="{{ route('pos.shifts.index') }}" method="GET" class="row row-sm align-items-end g-3">
                        <div class="col-md-4 col-xl-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Cashier</label>
                            <select name="user_id" class="form-control rounded-lg">
                                <option value="">All cashiers</option>
                                @foreach($cashiers as $id => $name)
                                    <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label class="small font-weight-bold text-muted text-uppercase">Status</label>
                            <select name="status" class="form-control rounded-lg">
                                <option value="">All</option>
                                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-xl-2"><label class="small font-weight-bold text-muted text-uppercase">From</label><input type="date" name="from_date" class="form-control rounded-lg" value="{{ request('from_date') }}"></div>
                        <div class="col-md-3 col-xl-2"><label class="small font-weight-bold text-muted text-uppercase">To</label><input type="date" name="to_date" class="form-control rounded-lg" value="{{ request('to_date') }}"></div>
                        <div class="col-md-auto mt-4 mt-xl-2"><button class="btn btn-primary rounded-xl px-4 mt-4 mt-xl-0 btn-block-md" type="submit"><i class="fe fe-filter mr-2"></i>Apply</button></div>
                    </form>
                </div>

                <div class="d-none d-md-block"><div class="table-responsive border rounded-xl shadow-sm mb-4">
                    <table class="table mb-0 table-hover">
                        <thead class="bg-light tx-11 text-muted text-uppercase font-weight-bold">
                            <tr>
                                <th>Terminal</th><th>Cashier</th><th>Opened</th><th>Closed</th>
                                <th>Open float</th><th>Close</th><th>Expected</th><th class="text-right">Variance</th><th>Status</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shifts as $shift)
                            <tr>
                                <td class="font-weight-semibold">{{ $shift->terminal_id }}</td>
                                <td>{{ $shift->user?->name }}</td>
                                <td>{{ $shift->opened_at->format('M d, H:i') }}</td>
                                <td>{{ $shift->closed_at ? $shift->closed_at->format('M d, H:i') : '—' }}</td>
                                <td>{{ number_format((float)$shift->opening_float, 2) }}</td>
                                <td>{{ $shift->closing_float !== null ? number_format((float)$shift->closing_float, 2) : '—' }}</td>
                                <td>{{ $shift->expected_cash !== null ? number_format((float)$shift->expected_cash, 2) : '—' }}</td>
                                <td class="text-right">
                                    @if($shift->status === 'closed')
                                        <span class="chip-var {{ (float)$shift->variance === 0.0 ? 'ok' : 'bad' }}">{{ number_format((float)$shift->variance, 2) }}</span>
                                    @else
                                        <span class="text-muted tx-13">Running</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-{{ $shift->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($shift->status) }}</span></td>
                                <td class="text-right">
                                    <a href="{{ route('pos.shifts.show', $shift) }}" class="btn btn-sm btn-outline-primary rounded-xl">Analyze</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center py-5 text-muted">No shifts mapped to your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div></div>

                <div class="d-md-none px-2">
                    @foreach($shifts as $shift)
                        <div class="border rounded-xl p-4 mb-3 shadow-sm bg-white">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="font-weight-bold">{{ $shift->terminal_id }}</div>
                                @if($shift->status === 'closed')
                                    <span class="chip-var {{ (float)$shift->variance === 0.0 ? 'ok' : 'bad' }}">{{ number_format((float)$shift->variance, 2) }}</span>
                                @else
                                    <span class="badge badge-success">Running</span>
                                @endif
                            </div>
                            <small class="text-muted d-block mb-3">{{ $shift->user?->name }}</small>
                            <div class="d-flex justify-content-between tx-13 text-muted mb-3">
                                <span>{{ $shift->opened_at->format('M d H:i') }}</span>
                                <span>{{ $shift->closed_at ? $shift->closed_at->format('H:i') : 'Active' }}</span>
                            </div>
                            <a href="{{ route('pos.shifts.show', $shift) }}" class="btn btn-block btn-outline-primary rounded-xl">Open reconciliation</a>
                        </div>
                    @endforeach
                    @if($shifts->isEmpty())
                        <p class="text-center text-muted py-5 mb-0">No shifts synced.</p>
                    @endif
                </div>

                @if($shifts->hasPages())
                    <div class="mt-2">{{ $shifts->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
