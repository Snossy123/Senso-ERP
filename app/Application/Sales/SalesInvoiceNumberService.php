<?php

namespace App\Application\Sales;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SalesInvoiceNumberService
{
    protected const SEQUENCE_KEY = 'sales_invoice_next_seq';

    public function reserve(int $tenantId): string
    {
        return DB::transaction(function () use ($tenantId) {
            $prefix = (string) Setting::get('invoice_prefix', 'INV-', $tenantId);
            $sequence = $this->lockAndIncrement($tenantId);

            return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    protected function lockAndIncrement(int $tenantId): int
    {
        $row = Setting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', self::SEQUENCE_KEY)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            Setting::set(self::SEQUENCE_KEY, '1', 'business', $tenantId);
            $row = Setting::query()
                ->where('tenant_id', $tenantId)
                ->where('key', self::SEQUENCE_KEY)
                ->lockForUpdate()
                ->first();
        }

        $current = (int) ($row->value ?? 1);
        $row->update(['value' => (string) ($current + 1)]);

        return $current;
    }
}
