<?php

namespace App\Services\Platform;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformSetting;
use App\Models\Tenant;

class PlatformInvoiceService
{
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
