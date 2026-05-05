<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (in_array('admin', $roles) && $user->isAdmin()) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->role?->slug === $role || $user->$role()) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Forbidden. Insufficient role.'], 403);
        }
        abort(403, 'You do not have the required role.');
    }
}
