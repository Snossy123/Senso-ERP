<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantStaff
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * ERP routes: tenant-bound staff only. Platform operators use /platform/* (login-as for ERP).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPlatformOperator()) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, __('messages.errors.tenant_staff_only'));
            }

            return redirect()->route('platform.dashboard');
        }

        if ($user && $user->tenant_id && ! $this->tenantManager->getCurrentId()) {
            return $this->denyTenantApplicationAccess($request);
        }

        return $next($request);
    }

    protected function denyTenantApplicationAccess(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(Response::HTTP_FORBIDDEN, __('messages.errors.tenant_access_unavailable'));
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => __('messages.errors.tenant_access_unavailable')]);
    }
}
