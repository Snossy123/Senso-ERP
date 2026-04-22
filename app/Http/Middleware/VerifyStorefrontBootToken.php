<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStorefrontBootToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.storefront.boot_token', '');
        if ($expected === '') {
            abort(503, 'Storefront boot API is not configured.');
        }

        $provided =
            (string) $request->bearerToken()
            ?: (string) $request->header('X-Storefront-Boot-Token', '')
            ?: (string) $request->query('token', '');

        if (!hash_equals($expected, $provided)) {
            abort(401, 'Invalid storefront boot token.');
        }

        return $next($request);
    }
}
