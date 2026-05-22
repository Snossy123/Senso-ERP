<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantManager
{
    protected ?Tenant $currentTenant = null;

    public function getCurrent(): ?Tenant
    {
        if ($this->currentTenant === null && Session::has('tenant_id')) {
            $this->currentTenant = Tenant::find(Session::get('tenant_id'));
        }

        return $this->currentTenant;
    }

    public function setCurrent(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        Session::put('tenant_id', $tenant->id);
    }

    public function getCurrentId(): ?int
    {
        $id = $this->currentTenant?->id ?? Session::get('tenant_id');

        return $id !== null ? (int) $id : null;
    }

    public function getFromRequest(): ?Tenant
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }

        if (Session::has('tenant_id')) {
            return Tenant::find(Session::get('tenant_id'));
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
        Session::forget('tenant_id');
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
