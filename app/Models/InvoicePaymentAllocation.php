<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePaymentAllocation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'invoice_payment_id', 'sales_invoice_id',
        'invoice_installment_id', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(InvoiceInstallment::class, 'invoice_installment_id');
    }
}
