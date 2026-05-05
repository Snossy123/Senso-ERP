<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformOperator
{
    /**
     * SaaS platform routes: operator accounts have no tenant_id.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->tenant_id !== null) {
            abort(Response::HTTP_FORBIDDEN, __('messages.errors.platform_only'));
        }

        return $next($request);
    }
}
