<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageTracking extends Model
{
    protected $fillable = [
        'tenant_id',
        'resource',
        'current_usage',
        'capacity_limit',
        'reset_at',
    ];

    protected $casts = [
        'current_usage' => 'integer',
        'capacity_limit' => 'integer',
        'reset_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAtLimit(): bool
    {
        return $this->current_usage >= $this->capacity_limit && $this->capacity_limit > 0;
    }

    public function getPercentageAttribute(): int
    {
        if ($this->capacity_limit <= 0) {
            return 0;
        }
        return (int) (($this->current_usage / $this->capacity_limit) * 100);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->capacity_limit - $this->current_usage);
    }

    public function incrementUsage(int $amount = 1): void
    {
        $this->increment('current_usage', $amount);
    }

    public function decrementUsage(int $amount = 1): void
    {
        $this->decrement('current_usage', $amount);
    }

    public function reset(): void
    {
        $this->update([
            'current_usage' => 0,
            'reset_at' => now(),
        ]);
    }
}
