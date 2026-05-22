<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanModule extends Model
{
    protected $fillable = [
        'plan_id',
        'module_key',
        'enabled',
        'limits',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'limits' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function platformModule(): BelongsTo
    {
        return $this->belongsTo(PlatformModule::class, 'module_key', 'key');
    }

    public function limitsSummary(): string
    {
        if (! $this->enabled || empty($this->limits)) {
            return '—';
        }

        $parts = [];
        foreach ($this->limits as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = str_replace('_', ' ', $key);
                }

                continue;
            }
            if ($value !== null && $value !== '') {
                $parts[] = $key.': '.$value;
            }
        }

        return $parts ? implode(', ', array_slice($parts, 0, 3)) : '—';
    }
}
