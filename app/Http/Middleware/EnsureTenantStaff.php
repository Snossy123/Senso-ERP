<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantStaff
{
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

        return $next($request);
    }
}
