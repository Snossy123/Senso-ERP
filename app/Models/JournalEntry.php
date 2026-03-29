<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'reference',
        'date',
        'description',
        'status',
        'created_by',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function getTotalDebitAttribute()
    {
        return $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->lines()->sum('credit');
    }

    // Check if the journal entry is balanced
    public function getIsBalancedAttribute()
    {
        // Use a small epsilon to account for decimal precision issues
        return abs($this->total_debit - $this->total_credit) < 0.0001;
    }
}
