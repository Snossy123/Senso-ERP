<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantManager;
use App\Models\Tenant;

class TenantMiddleware
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        if (auth()->check() && auth()->user()->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
        } elseif ($request->header('X-Tenant-ID')) {
            $tenant = Tenant::find($request->header('X-Tenant-ID'));
        } elseif ($request->subdomain) {
            $tenant = Tenant::where('domain', $request->getHost())->first();
        }

        if ($tenant && $tenant->isActive()) {
            $this->tenantManager->setCurrent($tenant);
        }

        return $next($request);
    }
}
