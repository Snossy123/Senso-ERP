@php
    $shipment = $shipment ?? null;
    $shippingIntegration = $shippingIntegration ?? null;
    $canManage = $canManage ?? false;
    $refreshUrl = $refreshUrl ?? null;
    $createUrl = $createUrl ?? null;
    $updateUrl = $updateUrl ?? null;
    $createMode = $createMode ?? null;
    $shippingRates = $shippingRates ?? collect();
@endphp
<div class="card mt-3">
    <div class="card-header"><h5 class="mb-0">{{ __('shipping.tracking') }}</h5></div>
    <div class="card-body">
        @if($shipment)
            <p class="mb-1"><strong>{{ __('shipping.serial') }}:</strong> <code>{{ $shipment->carrier_serial ?: '—' }}</code></p>
            <p class="mb-1">
                <strong>{{ __('shipping.qp_status') }}:</strong>
                <span class="badge badge-{{ $shipment->statusBadge() }}">{{ $shipment->status ?: '—' }}</span>
            </p>
            <p class="mb-1"><strong>{{ __('shipping.qp_fees') }}:</strong> {{ number_format((float) $shipment->total_fees, 2) }}</p>
            <p class="mb-1"><strong>{{ __('shipping.last_sync') }}:</strong> {{ $shipment->last_synced_at?->format('Y-m-d H:i') ?? '—' }}</p>
            @if($shipment->status_note)
                <p class="mb-2 text-muted tx-13">{{ $shipment->status_note }}</p>
            @endif
            @if($canManage && $refreshUrl)
                <form method="POST" action="{{ $refreshUrl }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-block btn-sm">{{ __('shipping.refresh_shipment') }}</button>
                </form>
            @endif
            @if($canManage && $updateUrl && $shipment->isPending())
                <hr>
                <h6 class="tx-13 text-uppercase text-muted">{{ __('shipping.edit_pending') }}</h6>
                <form method="POST" action="{{ $updateUrl }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>{{ __('shipping.full_name') }}</label>
                        <input type="text" name="full_name" class="form-control" required value="{{ old('full_name', $shipment->full_name) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.phone') }}</label>
                        <input type="text" name="phone" class="form-control" required value="{{ old('phone', $shipment->phone) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.address') }}</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $shipment->address) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.city') }}</label>
                        @if($shippingRates->isNotEmpty())
                            <select name="city" class="form-control" required>
                                @foreach($shippingRates as $rate)
                                    <option value="{{ $rate->city }}" @selected(old('city', $shipment->city) === $rate->city)>{{ $rate->displayName() }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="city" class="form-control" required value="{{ old('city', $shipment->city) }}">
                        @endif
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $notes ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-sm">{{ __('shipping.update_shipment') }}</button>
                </form>
            @endif
        @elseif(! ($shippingIntegration?->isConfigured()))
            <p class="text-muted mb-0 tx-13">{{ __('shipping.not_configured') }}</p>
            @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('settings.view'))
                <a href="{{ route('admin.shipping.index') }}" class="btn btn-link btn-sm px-0">{{ __('shipping.title') }}</a>
            @endif
        @elseif($canManage && $createUrl)
            <form method="POST" action="{{ $createUrl }}">
                @csrf
                @if($createMode === 'invoice')
                    @php $customer = $invoice->customer ?? null; @endphp
                    <div class="form-group">
                        <label>{{ __('shipping.full_name') }}</label>
                        <input type="text" name="full_name" class="form-control" required value="{{ old('full_name', $customer?->name) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.phone') }}</label>
                        <input type="text" name="phone" class="form-control" required value="{{ old('phone', $customer?->phone) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.address') }}</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $customer?->address) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.city') }}</label>
                        @if($shippingRates->isNotEmpty())
                            <select name="city" class="form-control" required>
                                @foreach($shippingRates as $rate)
                                    <option value="{{ $rate->city }}" @selected(old('city', $customer?->city) === $rate->city)>{{ $rate->displayName() }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="city" class="form-control" required value="{{ old('city', $customer?->city) }}">
                        @endif
                    </div>
                @endif
                <div class="form-group">
                    <label>{{ __('shipping.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $notes ?? '') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">{{ __('shipping.create_shipment') }}</button>
            </form>
        @endif
    </div>
</div>
