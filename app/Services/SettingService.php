<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    protected string $cachePrefix = 'tenant_settings_';

    /**
     * Get setting value.
     */
    public function get(string $key, $default = null, $tenantId = null)
    {
        $tenantId = $tenantId ?? app(TenantManager::class)->getCurrentId();
        if (!$tenantId) return $default;

        $cacheKey = $this->cachePrefix . $tenantId;

        $settings = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return Setting::where('tenant_id', $tenantId)->get()->pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Set setting value.
     */
    public function set(string $key, $value, string $group = 'general', $tenantId = null)
    {
        $tenantId = $tenantId ?? app(TenantManager::class)->getCurrentId();
        if (!$tenantId) return null;

        $setting = Setting::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget($this->cachePrefix . $tenantId);

        return $setting;
    }

    /**
     * Get all settings grouped.
     */
    public function allGrouped($tenantId = null)
    {
        $tenantId = $tenantId ?? app(TenantManager::class)->getCurrentId();
        return Setting::where('tenant_id', $tenantId)->get()->groupBy('group');
    }
}
