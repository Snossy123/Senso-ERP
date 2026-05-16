<?php

namespace App\Services\POS;

use App\Models\PosShift;
use App\Models\SaleRefund;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;

class ShiftSummaryService
{
    public function summarize(PosShift $shift): array
    {
        $salesQuery = $shift->sales()->where('status', 'completed');
        $saleIds = (clone $salesQuery)->pluck('id');

        $totalOrders = (clone $salesQuery)->count();
        $totalSales = (float) (clone $salesQuery)->sum('total');

        $totalRefunds = 0.0;
        if ($saleIds->isNotEmpty()) {
            $totalRefunds = (float) SaleRefund::query()
                ->whereIn('sale_id', $saleIds)
                ->sum('amount');
        }

        $netSales = $totalSales - $totalRefunds;

        $paymentRows = (clone $salesQuery)
            ->select('payment_method', DB::raw('COUNT(*) as txn_count'), DB::raw('SUM(total) as amount'))
            ->groupBy('payment_method')
            ->get();

        $paymentSummary = $paymentRows->map(function ($row) use ($totalSales) {
            $amount = (float) $row->amount;
            $pct = $totalSales > 0 ? round(($amount / $totalSales) * 100, 1) : 0;

            return [
                'method' => $row->payment_method,
                'label' => strtoupper(str_replace('_', ' ', $row->payment_method)),
                'txn_count' => (int) $row->txn_count,
                'amount' => $amount,
                'percent' => $pct,
            ];
        })->values()->all();

        $end = $shift->closed_at ?? now();
        $minutes = max(0, $shift->opened_at->diffInMinutes($end));
        $durationHuman = CarbonInterval::minutes($minutes)->cascade()->forHumans(short: true);

        return [
            'duration_minutes' => $minutes,
            'duration_human' => $durationHuman,
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
            'total_refunds' => $totalRefunds,
            'net_sales' => $netSales,
            'payment_summary' => $paymentSummary,
        ];
    }
}
