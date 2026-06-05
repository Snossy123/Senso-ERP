<?php

namespace App\Http\Controllers;

use App\Services\GoLiveChecklistService;
use App\Services\TenantManager;

class GoLiveController extends Controller
{
    public function __construct(
        private readonly GoLiveChecklistService $checklist,
        private readonly TenantManager $tenantManager,
    ) {}

    public function index()
    {
        $tenant = $this->tenantManager->getCurrent();

        abort_if(! $tenant, 403, 'Tenant context required.');

        $items = $this->checklist->itemsForTenant($tenant);
        $percent = $this->checklist->completionPercentage($tenant);
        $ready = $this->checklist->isReadyForGoLive($tenant);

        return view('erp.go-live.index', compact('tenant', 'items', 'percent', 'ready'));
    }
}
