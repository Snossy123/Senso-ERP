<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Platform\ImpersonationService;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected ImpersonationService $impersonation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant && $this->shouldBindTenant($tenant)) {
            $this->tenantManager->setCurrent($tenant);
        } else {
            $this->tenantManager->clear();
        }

        if ($this->isStoreRequest($request) && ! $this->tenantManager->getCurrent()) {
            abort(404, 'Store is not available for this domain.');
        }

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return Tenant::find(auth()->user()->tenant_id);
        }

        if ($request->header('X-Tenant-ID')) {
            return Tenant::find($request->header('X-Tenant-ID'));
        }

        $host = $request->getHost();

        if ($host !== '') {
            $byDomain = Tenant::query()
                ->where('domain', $host)
                ->where('is_active', true)
                ->first();

            if ($byDomain) {
                return $byDomain;
            }
        }

        return null;
    }

    protected function isStoreRequest(Request $request): bool
    {
        return $request->is('store', 'store/*');
    }

    protected function shouldBindTenant(Tenant $tenant): bool
    {
        if ($tenant->allowsApplicationAccess()) {
            return true;
        }

        return $this->impersonation->isActive()
            && (int) session('admin_logged_in_as_tenant') === (int) $tenant->id;
    }
}
