<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosShift extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'pos_shifts';

    protected $fillable = [
        'tenant_id', 'user_id', 'terminal_id', 'warehouse_id',
        'opening_float', 'closing_float', 'expected_cash', 'variance',
        'opened_at', 'closed_at', 'status', 'notes',
    ];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'closing_float' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function totalSales(): float
    {
        return (float) $this->sales()->where('status', 'completed')->sum('total');
    }

    public function totalCashSales(): float
    {
        return (float) $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('total');
    }

    public function close(float $closingFloat, ?string $notes = null): void
    {
        $expectedCash = $this->opening_float + $this->totalCashSales();
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closing_float' => $closingFloat,
            'expected_cash' => $expectedCash,
            'variance' => $closingFloat - $expectedCash,
            'notes' => $notes,
        ]);

        // Record variance in accounting if non-zero
        if (abs($this->variance) >= 0.01) {
            try {
                $generator = \App\Services\Accounting\JournalEntryFactory::getGenerator($this);
                $jeData = $generator->generate($this);

                app(\App\Services\AccountingService::class)->createJournalEntry(
                    $jeData['header'],
                    $jeData['lines']
                );
            } catch (\Exception $e) {
                // Log error but allow shift to close
                \Illuminate\Support\Facades\Log::error('Failed to create journal entry for shift variance: '.$e->getMessage());
            }
        }
    }
}
