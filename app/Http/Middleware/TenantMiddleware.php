<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if ($tenant && $this->shouldBindTenant($tenant)) {
            $this->tenantManager->setCurrent($tenant);
        } else {
            $this->tenantManager->clear();
        }

        return $next($request);
    }

    protected function shouldBindTenant(Tenant $tenant): bool
    {
        if ($tenant->allowsApplicationAccess()) {
            return true;
        }

        // Platform impersonation may access ERP for suspended/expired tenants.
        return session()->has('platform_operator_id');
    }
}
