<?php

namespace App\Application\Sales;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Inventory\StockPostingData;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\Accounting\JournalEntryFactory;
use App\Services\AccountingService;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesInvoiceService
{
    public function __construct(
        protected SalesInvoiceNumberService $numberService,
        protected InstallmentScheduleService $installmentSchedule,
        protected InventoryPostingService $inventoryPosting,
        protected AccountingService $accountingService,
        protected TenantManager $tenantManager,
    ) {}

    /**
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, int $userId): SalesInvoice
    {
        return DB::transaction(function () use ($header, $lines, $userId) {
            $tenantId = (int) $this->tenantManager->getCurrentId();
            $totals = $this->calculateTotals($lines, (float) ($header['discount_amount'] ?? 0));

            $invoice = SalesInvoice::create([
                'tenant_id' => $tenantId,
                'invoice_number' => 'DRAFT-'.uniqid(),
                'customer_id' => $header['customer_id'],
                'user_id' => $userId,
                'warehouse_id' => $header['warehouse_id'] ?? $this->resolveDefaultWarehouseId($tenantId),
                'status' => 'draft',
                'payment_term' => $header['payment_term'] ?? 'credit',
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'paid_amount' => 0,
                'balance_due' => $totals['total'],
                'payment_status' => 'unpaid',
                'invoice_date' => $header['invoice_date'] ?? now()->toDateString(),
                'due_date' => $header['due_date'] ?? null,
                'notes' => $header['notes'] ?? null,
            ]);

            $this->syncLines($invoice, $lines);

            return $invoice->load(['lines.product', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function updateDraft(SalesInvoice $invoice, array $header, array $lines): SalesInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be updated.');
        }

        return DB::transaction(function () use ($invoice, $header, $lines) {
            $totals = $this->calculateTotals($lines, (float) ($header['discount_amount'] ?? 0));

            $invoice->update([
                'customer_id' => $header['customer_id'] ?? $invoice->customer_id,
                'warehouse_id' => $header['warehouse_id'] ?? $invoice->warehouse_id,
                'payment_term' => $header['payment_term'] ?? $invoice->payment_term,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'balance_due' => $totals['total'],
                'invoice_date' => $header['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $header['due_date'] ?? $invoice->due_date,
                'notes' => $header['notes'] ?? $invoice->notes,
            ]);

            $invoice->lines()->delete();
            $this->syncLines($invoice, $lines);

            return $invoice->fresh(['lines.product', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>|null  $installmentPlan
     */
    public function confirm(SalesInvoice $invoice, ?array $installmentPlan = null, ?int $userId = null): SalesInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be confirmed.');
        }

        if ($invoice->lines()->count() < 1) {
            throw new InvalidArgumentException('Invoice must have at least one line.');
        }

        $userId = $userId ?? (int) Auth::id();

        return DB::transaction(function () use ($invoice, $installmentPlan, $userId) {
            $invoice->load('lines.product');

            foreach ($invoice->lines as $line) {
                $product = $line->product;
                if (! $product) {
                    continue;
                }
                if ((int) $product->stock_quantity < (int) $line->quantity) {
                    throw new InvalidArgumentException("Insufficient stock for {$product->name}.");
                }
            }

            $invoice->invoice_number = $this->numberService->reserve((int) $invoice->tenant_id);
            $invoice->status = 'confirmed';
            $invoice->confirmed_at = now();
            $invoice->user_id = $userId;
            $invoice->balance_due = (float) $invoice->total;
            $invoice->recalculatePaymentStatus();
            $invoice->save();

            foreach ($invoice->lines as $line) {
                $product = $line->product;
                if (! $product) {
                    continue;
                }

                $this->inventoryPosting->postOutbound(
                    StockPostingData::forSalesInvoiceLine(
                        tenantId: (int) $invoice->tenant_id,
                        productId: $product->id,
                        productVariantId: $line->product_variant_id,
                        warehouseId: $invoice->warehouse_id,
                        quantity: (int) $line->quantity,
                        unitCost: (float) $product->purchase_price,
                        totalValue: (int) $line->quantity * (float) $product->purchase_price,
                        invoiceNumber: $invoice->invoice_number,
                        userId: $userId,
                    )
                );
            }

            $this->postInvoiceJournal($invoice);

            if ($invoice->payment_term === 'installment' && $installmentPlan) {
                $this->installmentSchedule->createForInvoice($invoice, $installmentPlan);
            }

            return $invoice->fresh(['lines.product', 'customer', 'installmentPlan', 'installments']);
        });
    }

    public function cancelDraft(SalesInvoice $invoice): SalesInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be cancelled without reversal.');
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice;
    }

    public function cancelConfirmed(SalesInvoice $invoice): SalesInvoice
    {
        if (! $invoice->isConfirmed()) {
            throw new InvalidArgumentException('Only confirmed invoices can use this cancel.');
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new InvalidArgumentException('Cannot cancel an invoice with payments recorded.');
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{subtotal: float, discount_amount: float, tax_amount: float, total: float}
     */
    public function calculateTotals(array $lines, float $orderDiscount = 0): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;

        foreach ($lines as $line) {
            $qty = (int) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $lineDiscount = (float) ($line['discount'] ?? 0);
            $taxRate = (float) ($line['tax_rate'] ?? 0);

            $lineGross = $qty * $unitPrice;
            $lineNet = max(0, $lineGross - $lineDiscount);
            $subtotal += $lineNet;
            $taxAmount += round($lineNet * $taxRate / 100, 2);
        }

        $orderDiscount = max(0, $orderDiscount);
        $subtotalAfterDisc = max(0, $subtotal - $orderDiscount);
        $taxRatio = $subtotal > 0 ? $subtotalAfterDisc / $subtotal : 1;
        $taxAmount = round($taxAmount * $taxRatio, 2);
        $total = round($subtotalAfterDisc + $taxAmount, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($orderDiscount, 2),
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function syncLines(SalesInvoice $invoice, array $lines): void
    {
        foreach ($lines as $line) {
            $product = isset($line['product_id']) ? Product::find($line['product_id']) : null;
            $qty = (int) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? ($product?->selling_price ?? 0));
            $lineDiscount = (float) ($line['discount'] ?? 0);
            $lineGross = $qty * $unitPrice;
            $lineTotal = max(0, round($lineGross - $lineDiscount, 2));

            SalesInvoiceLine::create([
                'tenant_id' => $invoice->tenant_id,
                'sales_invoice_id' => $invoice->id,
                'product_id' => $line['product_id'] ?? null,
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'description' => $line['description'] ?? $product?->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $lineDiscount,
                'tax_rate' => (float) ($line['tax_rate'] ?? 0),
                'line_total' => $lineTotal,
            ]);
        }
    }

    protected function postInvoiceJournal(SalesInvoice $invoice): void
    {
        $exists = JournalEntry::query()
            ->where('source_type', SalesInvoice::class)
            ->where('source_id', $invoice->id)
            ->exists();

        if ($exists) {
            return;
        }

        $generator = JournalEntryFactory::getGenerator($invoice);
        $jeData = $generator->generate($invoice);
        $jeData['header']['tenant_id'] = $invoice->tenant_id;

        $this->accountingService->createJournalEntry($jeData['header'], $jeData['lines']);
    }

    protected function resolveDefaultWarehouseId(int $tenantId): ?int
    {
        $fromSetting = Setting::get('default_warehouse_id', null, $tenantId);
        if ($fromSetting) {
            return (int) $fromSetting;
        }

        return Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }
}
