<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceInstallment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'invoice_installment_plan_id', 'sales_invoice_id',
        'sequence', 'due_date', 'amount', 'paid_amount', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvoiceInstallmentPlan::class, 'invoice_installment_plan_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function refreshStatus(): void
    {
        $paid = (float) $this->paid_amount;
        $amount = (float) $this->amount;

        if ($paid + 0.009 >= $amount) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }
}
