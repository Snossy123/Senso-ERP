<?php

namespace App\Services\Platform;

use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\PlatformModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function create(array $data, array $modules = []): Plan
    {
        return DB::transaction(function () use ($data, $modules) {
            $plan = Plan::create($this->planAttributes($data));
            $this->syncModules($plan, $modules);
            $this->syncFeaturesFromModules($plan);

            return $plan->fresh(['planModules']);
        });
    }

    public function update(Plan $plan, array $data, array $modules = []): Plan
    {
        return DB::transaction(function () use ($plan, $data, $modules) {
            $plan->update($this->planAttributes($data, $plan));
            $this->syncModules($plan, $modules);
            $this->syncFeaturesFromModules($plan);

            return $plan->fresh(['planModules']);
        });
    }

    public function syncModules(Plan $plan, array $modules): void
    {
        $keys = PlatformModule::where('is_active', true)->pluck('key');

        foreach ($keys as $key) {
            $payload = $modules[$key] ?? null;
            $enabled = filter_var($payload['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $limits = $payload['limits'] ?? [];

            if (is_string($limits)) {
                $limits = json_decode($limits, true) ?? [];
            }

            PlanModule::updateOrCreate(
                ['plan_id' => $plan->id, 'module_key' => $key],
                [
                    'enabled' => $enabled,
                    'limits' => $enabled ? $this->normalizeLimits($key, $limits) : [],
                ]
            );
        }
    }

    public function syncFeaturesFromModules(Plan $plan): void
    {
        $features = [];

        $plan->load('planModules.platformModule');

        foreach ($plan->planModules as $planModule) {
            if (! $planModule->enabled) {
                continue;
            }

            $module = $planModule->platformModule
                ?? PlatformModule::where('key', $planModule->module_key)->first();

            if ($module?->feature_key) {
                $features[] = $module->feature_key;
            }
        }

        $plan->update(['features' => array_values(array_unique($features))]);
    }

    protected function normalizeLimits(string $moduleKey, array $limits): array
    {
        $schema = config("platform_modules.modules.{$moduleKey}.limits", []);
        $normalized = [];

        foreach ($schema as $field => $def) {
            if (! array_key_exists($field, $limits)) {
                continue;
            }

            $value = $limits[$field];
            if (($def['type'] ?? 'number') === 'boolean') {
                $normalized[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $normalized[$field] = is_numeric($value) ? (int) $value : $value;
            }
        }

        return $normalized;
    }

    protected function planAttributes(array $data, ?Plan $existing = null): array
    {
        $slug = $data['slug'] ?? null;
        if (! $slug) {
            $slug = Str::slug($data['name']);
            if ($existing && $slug === $existing->slug) {
                $slug = $existing->slug;
            } else {
                $base = $slug;
                $i = 1;
                while (Plan::where('slug', $slug)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                    $slug = $base.'-'.$i++;
                }
            }
        }

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'max_users' => $data['max_users'] ?? 5,
            'max_products' => $data['max_products'] ?? 100,
            'max_orders_per_month' => $data['max_orders_per_month'] ?? 100,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'trial_ends_at' => ! empty($data['trial_ends_at']) ? $data['trial_ends_at'] : null,
            'sort_order' => $data['sort_order'] ?? ($existing?->sort_order ?? 0),
        ];
    }
}
