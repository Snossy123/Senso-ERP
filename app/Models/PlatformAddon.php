<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformAddon extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_addon', 'platform_addon_id', 'plan_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format((float) $this->price, 2);
    }
}
