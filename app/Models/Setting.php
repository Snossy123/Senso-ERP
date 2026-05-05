<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null, $tenantId = null)
    {
        $tenantId = $tenantId ?? app(\App\Services\TenantManager::class)->getCurrentId();

        $setting = self::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, string $group = 'general', $tenantId = null)
    {
        $tenantId = $tenantId ?? app(\App\Services\TenantManager::class)->getCurrentId();

        $type = gettype($value);
        $typeMap = [
            'boolean' => 'boolean',
            'integer' => 'integer',
            'array' => 'json',
            'object' => 'json',
            'string' => 'string',
        ];

        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'key' => $key,
            ],
            [
                'group' => $group,
                'value' => $value,
                'type' => $typeMap[$type] ?? 'string',
            ]
        );
    }
}
