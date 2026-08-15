<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'city',
        'city_label',
        'fee',
        'is_active',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function displayName(): string
    {
        if (filled($this->city_label) && $this->city_label !== $this->city) {
            return $this->city_label.' ('.$this->city.')';
        }

        return (string) $this->city;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('city_label')->orderBy('city');
    }
}
