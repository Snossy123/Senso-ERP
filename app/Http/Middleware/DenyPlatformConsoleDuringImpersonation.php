<?php

namespace App\Http\Middleware;

use App\Services\Platform\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyPlatformConsoleDuringImpersonation
{
    public function __construct(
        protected ImpersonationService $impersonation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->isActive()) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, __('platform.impersonation.console_blocked'));
            }

            return redirect()
                ->route('dashboard')
                ->with('warning', __('platform.impersonation.console_blocked'));
        }

        return $next($request);
    }
}
