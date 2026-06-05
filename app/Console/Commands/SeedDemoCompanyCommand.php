<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\GoLiveChecklistService;
use Database\Seeders\DemoCompanySeeder;
use Illuminate\Console\Command;

class SeedDemoCompanyCommand extends Command
{
    protected $signature = 'demo:seed
                            {--slug=tech-store : Tenant slug to prepare as demo company}';

    protected $description = 'Prepare the Tech Store demo company with Arabic settings, provisioning, and showcase data';

    public function handle(GoLiveChecklistService $checklist): int
    {
        $slug = (string) $this->option('slug');

        $tenant = Tenant::where('slug', $slug)->first();
        if (! $tenant) {
            $this->error("Tenant [{$slug}] not found. Run php artisan db:seed first.");

            return self::FAILURE;
        }

        $this->info("Seeding demo company: {$tenant->name} ({$slug})...");
        $this->call('db:seed', ['--class' => DemoCompanySeeder::class]);

        $tenant->refresh();
        $pct = $checklist->completionPercentage($tenant);
        $ready = $checklist->isReadyForGoLive($tenant);

        $this->newLine();
        $this->info('Demo company is ready.');
        $this->table(
            ['Item', 'Value'],
            [
                ['Go-live progress', "{$pct}%"],
                ['Go-live ready', $ready ? 'Yes' : 'No'],
                ['ERP URL', rtrim((string) config('app.url'), '/').'/login'],
                ['Store URL', 'http://techstore.local/store'],
                ['Admin', 'admin@techstore.local / password'],
                ['Manager', 'manager@techstore.local / password'],
                ['Cashier', 'staff@techstore.local / password'],
            ]
        );

        $this->newLine();
        $this->comment('Add to /etc/hosts for storefront demo:');
        $this->line('  127.0.0.1  techstore.local');

        return self::SUCCESS;
    }
}
