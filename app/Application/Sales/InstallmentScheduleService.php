<?php

namespace App\Application\Sales;

use App\Models\InvoiceInstallment;
use App\Models\InvoiceInstallmentPlan;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use InvalidArgumentException;

class InstallmentScheduleService
{
    /**
     * @param  array{down_payment: float, installment_count: int, interval_days: int, first_due_date: string}  $planData
     */
    public function createForInvoice(SalesInvoice $invoice, array $planData): InvoiceInstallmentPlan
    {
        $count = (int) ($planData['installment_count'] ?? 0);
        if ($count < 1) {
            throw new InvalidArgumentException('Installment count must be at least 1.');
        }

        $downPayment = round((float) ($planData['down_payment'] ?? 0), 2);
        $intervalDays = max(1, (int) ($planData['interval_days'] ?? 30));
        $firstDue = Carbon::parse($planData['first_due_date'])->startOfDay();
        $total = (float) $invoice->total;
        $financed = max(0, round($total - $downPayment, 2));

        if ($financed <= 0) {
            throw new InvalidArgumentException('Installment total must be greater than down payment.');
        }

        $baseAmount = floor(($financed / $count) * 100) / 100;
        $remainder = round($financed - ($baseAmount * $count), 2);

        $plan = InvoiceInstallmentPlan::create([
            'tenant_id' => $invoice->tenant_id,
            'sales_invoice_id' => $invoice->id,
            'down_payment' => $downPayment,
            'installment_count' => $count,
            'interval_days' => $intervalDays,
            'first_due_date' => $firstDue->toDateString(),
        ]);

        for ($i = 0; $i < $count; $i++) {
            $amount = $baseAmount + ($i === $count - 1 ? $remainder : 0);
            $dueDate = $firstDue->copy()->addDays($intervalDays * $i);

            InvoiceInstallment::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_installment_plan_id' => $plan->id,
                'sales_invoice_id' => $invoice->id,
                'sequence' => $i + 1,
                'due_date' => $dueDate->toDateString(),
                'amount' => $amount,
                'paid_amount' => 0,
                'status' => $dueDate->isPast() ? 'overdue' : 'pending',
            ]);
        }

        return $plan;
    }

    public function markOverdueInstallments(int $tenantId): int
    {
        return InvoiceInstallment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }
}
