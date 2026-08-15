<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ShippingIntegration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'driver',
        'username',
        'password',
        'base_url',
        'is_active',
        'default_weight',
        'auto_create_on_checkout',
        'last_history_synced_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_create_on_checkout' => 'boolean',
        'default_weight' => 'decimal:3',
        'last_history_synced_at' => 'datetime',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if ($value === null || $value === '') {
                    return '';
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return $this->attributes['password'] ?? null;
                }

                return Crypt::encryptString($value);
            }
        );
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'tenant_id', 'tenant_id');
    }

    public function isConfigured(): bool
    {
        return $this->is_active
            && filled($this->username)
            && filled($this->password);
    }

    public function resolvedBaseUrl(): string
    {
        $url = filled($this->base_url) ? $this->base_url : (string) config('shipping.default_base_url');

        return rtrim($url, '/').'/';
    }
}
