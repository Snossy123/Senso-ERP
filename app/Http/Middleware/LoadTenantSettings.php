<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadTenantSettings
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = app(\App\Services\TenantManager::class)->getCurrentId();

        if ($tenantId) {
            $settings = app(SettingService::class)->allGrouped($tenantId);

            foreach ($settings as $group => $items) {
                foreach ($items as $item) {
                    // Load into Laravel config: tenant.group.key
                    config(["tenant.{$group}.{$item->key}" => $item->value]);

                    // Specific overrides for Laravel core
                    if ($group === 'localization') {
                        if ($item->key === 'language') {
                            app()->setLocale($item->value);
                        }
                        if ($item->key === 'timezone') {
                            config(['app.timezone' => $item->value]);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
