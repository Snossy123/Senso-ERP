<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformModule extends Model
{
    protected $fillable = [
        'key',
        'name',
        'icon',
        'sort_order',
        'is_active',
        'limit_schema',
        'feature_key',
    ];

    protected $casts = [
        'limit_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function planModules(): HasMany
    {
        return $this->hasMany(PlanModule::class, 'module_key', 'key');
    }

    public function defaultLimits(): array
    {
        $limits = [];
        foreach ($this->limit_schema ?? [] as $key => $schema) {
            $limits[$key] = $schema['default'] ?? null;
        }

        return $limits;
    }
}
