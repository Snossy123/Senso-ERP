<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'short_name', 'base_unit_id', 'operator', 'operator_value', 'is_active',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function subUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }
}
