<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'number',
        'amount',
        'currency',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'pending' => 'warning',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'info',
        };
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
