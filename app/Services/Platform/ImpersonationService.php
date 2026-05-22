<?php

namespace App\Services\Platform;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public const SESSION_KEYS = [
        'platform_operator_id',
        'admin_logged_in_as_tenant',
        'admin_logged_in_as_user',
    ];

    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function start(User $platformOperator, Tenant $tenant, User $tenantUser): void
    {
        session([
            'platform_operator_id' => $platformOperator->id,
            'admin_logged_in_as_tenant' => $tenant->id,
            'admin_logged_in_as_user' => $tenantUser->id,
        ]);

        Auth::login($tenantUser);
        $this->tenantManager->setCurrent($tenant->fresh());
    }

    public function isActive(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! $user->tenant_id || ! session()->has('platform_operator_id')) {
            return false;
        }

        if ((int) session('admin_logged_in_as_user') !== (int) $user->id) {
            return false;
        }

        if ((int) session('admin_logged_in_as_tenant') !== (int) $user->tenant_id) {
            return false;
        }

        return $this->platformOperator() !== null;
    }

    public function platformOperator(): ?User
    {
        $operatorId = session('platform_operator_id');

        if (! $operatorId) {
            return null;
        }

        $operator = User::withoutGlobalScopes()->find($operatorId);

        if (! $operator || ! $operator->isPlatformOperator()) {
            return null;
        }

        return $operator;
    }

    /**
     * End impersonation without restoring the platform operator session.
     * Callers must require a fresh login before platform console access.
     */
    public function end(Request $request): void
    {
        session()->forget(self::SESSION_KEYS);
        $this->tenantManager->clear();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
