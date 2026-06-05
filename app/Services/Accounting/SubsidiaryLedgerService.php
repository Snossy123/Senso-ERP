<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Light AR/AP view from open operational documents + mapped GL control accounts.
 */
class SubsidiaryLedgerService
{
    public function accountsReceivableSummary(int $tenantId): array
    {
        $openOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('payment_status', '!=', 'paid')
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('created_at')
            ->get();

        $creditSales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('payment_method', 'credit')
            ->where('payment_status', 'pending')
            ->where('status', 'completed')
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        $openItems = collect();

        foreach ($openOrders as $order) {
            $openItems->push([
                'type' => 'web_order',
                'id' => $order->id,
                'reference' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_id' => $order->customer_id,
                'total' => (float) $order->total,
                'date' => $order->created_at,
                'route' => route('admin.orders.show', $order),
            ]);
        }

        foreach ($creditSales as $sale) {
            $openItems->push([
                'type' => 'pos_credit',
                'id' => $sale->id,
                'reference' => $sale->sale_number,
                'customer_name' => $sale->customer_name ?? $sale->customer?->name ?? 'Walk-in',
                'customer_id' => $sale->customer_id,
                'total' => (float) $sale->total,
                'date' => $sale->created_at,
                'route' => route('pos.sales.index'),
            ]);
        }

        $aging = $this->buildAgingBuckets($openItems);

        $byCustomer = $openItems->groupBy(fn ($item) => $item['customer_id'] ?? 'guest:'.$item['customer_name'])
            ->map(function (Collection $items, string $key) {
                $first = $items->first();

                return [
                    'customer_id' => $first['customer_id'],
                    'customer_name' => $first['customer_name'],
                    'document_count' => $items->count(),
                    'open_balance' => round($items->sum('total'), 2),
                    'documents' => $items->values()->all(),
                ];
            })
            ->values();

        $glAccountId = AccountSetting::getAccountId('customer_receivable', $tenantId);
        $glBalance = $glAccountId ? $this->glAccountBalance($glAccountId) : 0.0;

        return [
            'gl_account_id' => $glAccountId,
            'gl_balance' => $glBalance,
            'open_document_total' => round($openItems->sum('total'), 2),
            'customers' => $byCustomer->all(),
            'aging' => $aging,
        ];
    }

    public function accountsPayableSummary(int $tenantId): array
    {
        $openPos = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->where('payment_status', '!=', 'paid')
            ->with('supplier')
            ->orderByDesc('received_at')
            ->orderByDesc('updated_at')
            ->get();

        $bySupplier = $openPos->groupBy('supplier_id')
            ->map(function (Collection $orders) {
                $first = $orders->first();

                return [
                    'supplier_id' => $first->supplier_id,
                    'supplier_name' => $first->supplier?->name ?? 'Unknown',
                    'document_count' => $orders->count(),
                    'open_balance' => round($orders->sum(fn (PurchaseOrder $po) => (float) $po->total_amount), 2),
                    'purchase_orders' => $orders->map(fn (PurchaseOrder $po) => [
                        'id' => $po->id,
                        'reference_no' => $po->reference_no,
                        'total_amount' => (float) $po->total_amount,
                        'received_at' => $po->received_at?->toDateString() ?? $po->updated_at?->toDateString(),
                    ])->values()->all(),
                ];
            })
            ->values();

        $glAccountId = AccountSetting::getAccountId('supplier_payable', $tenantId);
        $glBalance = $glAccountId ? $this->glAccountBalance($glAccountId) : 0.0;

        return [
            'gl_account_id' => $glAccountId,
            'gl_balance' => $glBalance,
            'open_document_total' => round($openPos->sum(fn (PurchaseOrder $po) => (float) $po->total_amount), 2),
            'suppliers' => $bySupplier->all(),
        ];
    }

    /**
     * @param  Collection<int, array>  $items
     */
    private function buildAgingBuckets(Collection $items): array
    {
        $buckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            'over_90' => 0.0,
        ];

        $today = Carbon::today();

        foreach ($items as $item) {
            $date = $item['date'] instanceof Carbon ? $item['date'] : Carbon::parse($item['date']);
            $days = $date->diffInDays($today);
            $amount = (float) $item['total'];

            if ($days <= 0) {
                $buckets['current'] += $amount;
            } elseif ($days <= 30) {
                $buckets['1_30'] += $amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['over_90'] += $amount;
            }
        }

        foreach ($buckets as $key => $value) {
            $buckets[$key] = round($value, 2);
        }

        return $buckets;
    }

    private function glAccountBalance(int $accountId): float
    {
        $account = Account::find($accountId);

        return $account ? (float) $account->balance : 0.0;
    }
}
