<?php

if (!function_exists('setting')) {
    /**
     * Get or set a setting value.
     */
    function setting($key = null, $default = null, $tenantId = null)
    {
        $service = app(\App\Services\SettingService::class);

        if (is_null($key)) {
            return $service;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $service->set($k, $v, 'general', $tenantId);
            }
            return true;
        }

        return $service->get($key, $default, $tenantId);
    }
}
