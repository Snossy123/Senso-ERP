<?php

namespace App\Services\Platform;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformSetting;
use App\Models\Tenant;

class PlatformInvoiceService
{
    /**
     * Create a pending platform invoice only when a paid plan upgrade warrants billing.
     * Skips duplicate invoices for the same plan and preserves an already-paid tenant state.
     */
    public function createForPlanUpgradeIfNeeded(Tenant $tenant, Plan $plan): ?PlatformInvoice
    {
        if ($plan->price <= 0) {
            return null;
        }

        $isSamePlan = (int) $tenant->plan_id === (int) $plan->id;

        if ($isSamePlan) {
            if ($tenant->payment_status === 'paid') {
                return null;
            }

            $hasPending = PlatformInvoice::query()
                ->where('tenant_id', $tenant->id)
                ->where('plan_id', $plan->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPending) {
                return null;
            }
        }

        return $this->createForTenant($tenant, $plan);
    }

    public function createForTenant(Tenant $tenant, ?Plan $plan = null): PlatformInvoice
    {
        $plan = $plan ?? $tenant->plan;
        $prefix = PlatformSetting::get('invoice_prefix', 'INV-');
        $next = (int) PlatformInvoice::max('id') + 1;
        $number = $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);

        return PlatformInvoice::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan?->id,
            'number' => $number,
            'amount' => $plan?->price ?? $tenant->price ?? 0,
            'currency' => $plan?->currency ?? $tenant->currency ?? 'USD',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(14),
            'metadata' => [
                'plan_name' => $plan?->name,
                'billing_cycle' => $plan?->billing_cycle ?? $tenant->billing_cycle,
            ],
        ]);
    }

    public function markPaid(PlatformInvoice $invoice): void
    {
        $invoice->markPaid();

        if ($invoice->tenant) {
            $invoice->tenant->update(['payment_status' => 'paid']);
        }
    }
}
