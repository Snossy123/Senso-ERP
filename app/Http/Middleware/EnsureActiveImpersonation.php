<?php

namespace App\Http\Middleware;

use App\Services\Platform\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveImpersonation
{
    public function __construct(
        protected ImpersonationService $impersonation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->impersonation->isActive()) {
            abort(Response::HTTP_FORBIDDEN, __('platform.impersonation.not_active'));
        }

        return $next($request);
    }
}
