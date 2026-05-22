<?php

namespace App\Services\Platform;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformInvoiceService
{
    public function __construct(
        protected TenantService $tenantService
    ) {}
    protected const INVOICE_SEQUENCE_KEY = 'invoice_next_sequence';

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

        return DB::transaction(function () use ($tenant, $plan) {
            return PlatformInvoice::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'number' => $this->reserveInvoiceNumber(),
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
        });
    }

    /**
     * Allocate the next invoice number under row lock to avoid duplicate numbers on concurrent upgrades.
     */
    protected function reserveInvoiceNumber(): string
    {
        $sequenceRow = $this->lockInvoiceSequenceRow();
        $sequence = (int) $sequenceRow->value;

        $sequenceRow->update(['value' => $sequence + 1]);
        Cache::forget('platform_setting.'.self::INVOICE_SEQUENCE_KEY);

        $prefix = PlatformSetting::where('key', 'invoice_prefix')->value('value') ?? 'INV-';

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    protected function lockInvoiceSequenceRow(): PlatformSetting
    {
        $row = PlatformSetting::query()
            ->where('key', self::INVOICE_SEQUENCE_KEY)
            ->lockForUpdate()
            ->first();

        if ($row) {
            return $row;
        }

        try {
            return PlatformSetting::create([
                'key' => self::INVOICE_SEQUENCE_KEY,
                'value' => (int) PlatformInvoice::max('id') + 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            return PlatformSetting::query()
                ->where('key', self::INVOICE_SEQUENCE_KEY)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }

    public function markPaid(PlatformInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->markPaid();

            $tenant = $invoice->tenant;

            if (! $tenant) {
                return;
            }

            $tenant->update(['payment_status' => 'paid']);
            $this->tenantService->activateTenant($tenant->fresh());
        });
    }
}
