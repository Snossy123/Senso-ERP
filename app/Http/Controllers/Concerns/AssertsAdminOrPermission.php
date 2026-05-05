<?php

namespace App\Http\Controllers\Concerns;

trait AssertsAdminOrPermission
{
    protected function assertAdminOrPermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }
        if ($user->isAdmin() || $user->hasPermission($permission)) {
            return;
        }
        abort(403, 'Access denied.');
    }
}
