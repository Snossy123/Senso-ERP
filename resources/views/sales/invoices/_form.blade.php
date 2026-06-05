@php
    $isEdit = isset($invoice);
    $lines = old('lines', $isEdit ? $invoice->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'description' => $l->description,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount' => $l->discount,
        'tax_rate' => $l->tax_rate,
    ])->toArray() : [['product_id' => '', 'quantity' => 1, 'unit_price' => 0, 'discount' => 0, 'tax_rate' => 0]]);
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
        'price' => (float) $p->selling_price,
    ])->values();
@endphp
<div class="row">
    <div class="col-md-4 form-group">
        <label>{{ __('sales_invoices.customer') }} *</label>
        <select name="customer_id" class="form-control" required>
            <option value="">—</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" @selected(old('customer_id', $invoice->customer_id ?? null) == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.warehouse') }}</label>
        <select name="warehouse_id" class="form-control">
            <option value="">{{ __('sales_invoices.default_warehouse') }}</option>
            @foreach($warehouses as $w)
            <option value="{{ $w->id }}" @selected(old('warehouse_id', $invoice->warehouse_id ?? null) == $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.payment_term') }} *</label>
        <select name="payment_term" id="payment_term" class="form-control" required>
            @foreach(['cash','credit','installment'] as $term)
            <option value="{{ $term }}" @selected(old('payment_term', $invoice->payment_term ?? 'credit') === $term)>{{ __('sales_invoices.term_'.$term) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.invoice_date') }}</label>
        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $isEdit ? $invoice->invoice_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.due_date') }}</label>
        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $isEdit ? $invoice->due_date?->format('Y-m-d') : '') }}">
    </div>
</div>

<div id="installment-fields" class="row border rounded p-3 mb-3" style="display:none;">
    <div class="col-md-3 form-group">
        <label>{{ __('sales_invoices.down_payment') }}</label>
        <input type="number" step="0.01" name="down_payment" class="form-control @error('down_payment') is-invalid @enderror" value="{{ old('down_payment', 0) }}">
        @error('down_payment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-muted" id="installment-total-hint"></small>
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.installment_count') }}</label>
        <input type="number" name="installment_count" class="form-control" min="1" value="{{ old('installment_count', 3) }}">
    </div>
    <div class="col-md-2 form-group">
        <label>{{ __('sales_invoices.interval_days') }}</label>
        <input type="number" name="interval_days" class="form-control" min="1" value="{{ old('interval_days', 30) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>{{ __('sales_invoices.first_due_date') }}</label>
        <input type="date" name="first_due_date" class="form-control" value="{{ old('first_due_date', now()->addMonth()->format('Y-m-d')) }}">
    </div>
</div>

<div class="form-group">
    <label>{{ __('sales_invoices.order_discount') }}</label>
    <input type="number" step="0.01" name="discount_amount" class="form-control" style="max-width:200px" value="{{ old('discount_amount', $invoice->discount_amount ?? 0) }}">
</div>
<div class="form-group">
    <label>{{ __('sales_invoices.notes') }}</label>
    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $invoice->notes ?? '') }}</textarea>
</div>

<h5 class="mt-3">{{ __('sales_invoices.lines') }}</h5>
<div class="table-responsive">
    <table class="table" id="lines-table">
        <thead>
            <tr>
                <th>{{ __('sales_invoices.product') }}</th>
                <th>{{ __('sales_invoices.qty') }}</th>
                <th>{{ __('sales_invoices.unit_price') }}</th>
                <th>{{ __('sales_invoices.discount') }}</th>
                <th>{{ __('sales_invoices.tax_pct') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="lines-body">
        @foreach($lines as $i => $line)
            <tr class="line-row">
                <td>
                    <select name="lines[{{ $i }}][product_id]" class="form-control form-control-sm product-select">
                        <option value="">—</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}" @selected(($line['product_id'] ?? '') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="lines[{{ $i }}][description]" class="line-desc" value="{{ $line['description'] ?? '' }}">
                </td>
                <td><input type="number" name="lines[{{ $i }}][quantity]" class="form-control form-control-sm" min="1" value="{{ $line['quantity'] ?? 1 }}" required></td>
                <td><input type="number" step="0.01" name="lines[{{ $i }}][unit_price]" class="form-control form-control-sm line-price" value="{{ $line['unit_price'] ?? 0 }}" required></td>
                <td><input type="number" step="0.01" name="lines[{{ $i }}][discount]" class="form-control form-control-sm" value="{{ $line['discount'] ?? 0 }}"></td>
                <td><input type="number" step="0.01" name="lines[{{ $i }}][tax_rate]" class="form-control form-control-sm" value="{{ $line['tax_rate'] ?? 0 }}"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">&times;</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-line">+ {{ __('sales_invoices.add_line') }}</button>

<script>
(function () {
    const products = @json($productOptions);
    let lineIndex = document.querySelectorAll('.line-row').length;

    function productOptions(selected) {
        let html = '<option value="">—</option>';
        products.forEach(p => {
            const sel = String(p.id) === String(selected) ? ' selected' : '';
            html += `<option value="${p.id}" data-price="${p.price}"${sel}>${p.name} (${p.sku})</option>`;
        });
        return html;
    }

    document.getElementById('add-line')?.addEventListener('click', function () {
        const tbody = document.getElementById('lines-body');
        const tr = document.createElement('tr');
        tr.className = 'line-row';
        tr.innerHTML = `
            <td><select name="lines[${lineIndex}][product_id]" class="form-control form-control-sm product-select">${productOptions('')}</select>
                <input type="hidden" name="lines[${lineIndex}][description]" class="line-desc"></td>
            <td><input type="number" name="lines[${lineIndex}][quantity]" class="form-control form-control-sm" min="1" value="1" required></td>
            <td><input type="number" step="0.01" name="lines[${lineIndex}][unit_price]" class="form-control form-control-sm line-price" value="0" required></td>
            <td><input type="number" step="0.01" name="lines[${lineIndex}][discount]" class="form-control form-control-sm" value="0"></td>
            <td><input type="number" step="0.01" name="lines[${lineIndex}][tax_rate]" class="form-control form-control-sm" value="0"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">&times;</button></td>`;
        tbody.appendChild(tr);
        lineIndex++;
        bindRow(tr);
    });

    function bindRow(row) {
        row.querySelector('.remove-line')?.addEventListener('click', () => {
            if (document.querySelectorAll('.line-row').length > 1) row.remove();
        });
        row.querySelector('.product-select')?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const price = opt?.dataset?.price || 0;
            row.querySelector('.line-price').value = price;
            row.querySelector('.line-desc').value = opt?.text?.split(' (')[0] || '';
        });
    }

    document.querySelectorAll('.line-row').forEach(bindRow);

    const term = document.getElementById('payment_term');
    const inst = document.getElementById('installment-fields');
    function toggleInst() {
        inst.style.display = term.value === 'installment' ? 'flex' : 'none';
        inst.style.flexWrap = 'wrap';
    }
    term?.addEventListener('change', toggleInst);
    toggleInst();

    function parseNum(el) {
        const v = parseFloat(el?.value);
        return Number.isFinite(v) ? v : 0;
    }

    function invoiceTotal() {
        let subtotal = 0;
        let tax = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const qty = parseNum(row.querySelector('[name*="[quantity]"]'));
            const price = parseNum(row.querySelector('.line-price'));
            const disc = parseNum(row.querySelector('[name*="[discount]"]'));
            const taxRate = parseNum(row.querySelector('[name*="[tax_rate]"]'));
            const lineNet = Math.max(0, qty * price - disc);
            subtotal += lineNet;
            tax += Math.round(lineNet * taxRate / 100 * 100) / 100;
        });
        const orderDisc = parseNum(document.querySelector('[name="discount_amount"]'));
        const subAfter = Math.max(0, subtotal - orderDisc);
        const ratio = subtotal > 0 ? subAfter / subtotal : 1;
        return Math.round((subAfter + tax * ratio) * 100) / 100;
    }

    function refreshInstallmentHint() {
        const hint = document.getElementById('installment-total-hint');
        const down = document.querySelector('[name="down_payment"]');
        if (!hint || term.value !== 'installment') return;
        const total = invoiceTotal();
        hint.textContent = '{{ __('sales_invoices.installment_total_hint') }}'.replace(':total', total.toFixed(2));
        if (down && parseNum(down) >= total && total > 0) {
            down.classList.add('is-invalid');
        } else if (down) {
            down.classList.remove('is-invalid');
        }
    }

    document.getElementById('lines-body')?.addEventListener('input', refreshInstallmentHint);
    document.querySelector('[name="discount_amount"]')?.addEventListener('input', refreshInstallmentHint);
    document.querySelector('[name="down_payment"]')?.addEventListener('input', refreshInstallmentHint);
    term?.addEventListener('change', refreshInstallmentHint);
    refreshInstallmentHint();
})();
</script>
