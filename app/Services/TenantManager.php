<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantManager
{
    protected ?Tenant $currentTenant = null;

    public function getCurrent(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function setCurrent(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        session(['tenant_id' => $tenant->id]);
    }

    public function getCurrentId(): ?int
    {
        return $this->currentTenant?->id ?? session('tenant_id');
    }

    public function getFromRequest(): ?Tenant
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }

        if (session()->has('tenant_id')) {
            return Tenant::find(session('tenant_id'));
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->currentTenant?->isActive() ?? false;
    }

    public function clear(): void
    {
        $this->currentTenant = null;
        session()->forget('tenant_id');
    }

    public function getSettings(): array
    {
        return $this->currentTenant?->settings ?? [];
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->currentTenant?->settings[$key] ?? $default;
    }
}
