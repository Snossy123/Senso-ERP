<?php

namespace App\Console\Commands;

use App\Application\Shipping\SyncQpShipmentsService;
use Illuminate\Console\Command;

class SyncQpShipmentsCommand extends Command
{
    protected $signature = 'shipping:sync-qp';

    protected $description = 'Poll QP Express for shipment status updates';

    public function handle(SyncQpShipmentsService $sync): int
    {
        $stats = $sync->syncAll();

        $this->info(sprintf(
            'QP sync complete. tenants=%d updated=%d errors=%d',
            $stats['tenants'],
            $stats['updated'],
            $stats['errors']
        ));

        return self::SUCCESS;
    }
}
