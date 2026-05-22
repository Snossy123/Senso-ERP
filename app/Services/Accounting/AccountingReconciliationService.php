<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

class AccountingReconciliationService
{
    /**
     * @return Collection<int, array{document_type: string, document_id: int, reference: string, date: string}>
     */
    public function missingJournalEntries(?int $tenantId, int $limit = 100): Collection
    {
        $missing = collect();

        if (! $tenantId) {
            return $missing;
        }

        Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (Sale $sale) use ($missing) {
                if (! $this->hasJournal(Sale::class, $sale->id)) {
                    $missing->push([
                        'document_type' => 'POS Sale',
                        'document_id' => $sale->id,
                        'reference' => $sale->sale_number,
                        'date' => $sale->created_at?->toDateString() ?? '',
                    ]);
                }
            });

        Order::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'delivered'])
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (Order $order) use ($missing, $tenantId) {
                $tenant = $order->tenant;
                $shouldHave = $order->payment_status === 'paid'
                    || ($tenant && CommerceRevenueRecognition::shouldRecognizeOnCheckout($tenant));

                if ($shouldHave && ! $this->hasJournal(Order::class, $order->id)) {
                    $missing->push([
                        'document_type' => 'Web Order',
                        'document_id' => $order->id,
                        'reference' => $order->order_number,
                        'date' => $order->created_at?->toDateString() ?? '',
                    ]);
                }
            });

        PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (PurchaseOrder $po) use ($missing) {
                if (! $this->hasJournal(PurchaseOrder::class, $po->id)) {
                    $missing->push([
                        'document_type' => 'Purchase Order',
                        'document_id' => $po->id,
                        'reference' => $po->reference_no,
                        'date' => $po->received_at?->toDateString() ?? $po->updated_at?->toDateString() ?? '',
                    ]);
                }
            });

        SaleRefund::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (SaleRefund $refund) use ($missing) {
                if (! $this->hasJournal(SaleRefund::class, $refund->id)) {
                    $missing->push([
                        'document_type' => 'POS Refund',
                        'document_id' => $refund->id,
                        'reference' => $refund->refund_number,
                        'date' => $refund->created_at?->toDateString() ?? '',
                    ]);
                }
            });

        SupplierPayment::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (SupplierPayment $payment) use ($missing) {
                if (! $this->hasJournal(SupplierPayment::class, $payment->id)) {
                    $missing->push([
                        'document_type' => 'Supplier Payment',
                        'document_id' => $payment->id,
                        'reference' => $payment->payment_number,
                        'date' => $payment->payment_date?->toDateString() ?? '',
                    ]);
                }
            });

        CustomerPayment::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(500)
            ->get()
            ->each(function (CustomerPayment $payment) use ($missing) {
                if (! $this->hasJournal(CustomerPayment::class, $payment->id)) {
                    $missing->push([
                        'document_type' => 'Customer Payment',
                        'document_id' => $payment->id,
                        'reference' => $payment->payment_number,
                        'date' => $payment->payment_date?->toDateString() ?? '',
                    ]);
                }
            });

        return $missing->take($limit)->values();
    }

    private function hasJournal(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }
}
