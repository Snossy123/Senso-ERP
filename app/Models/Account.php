<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    // Helper to get total balance considering debit/credit based on account type
    // Asset/Expense increase with debit; Liability/Equity/Revenue increase with credit
    public function getBalanceAttribute()
    {
        $sumDebit = $this->journalEntryLines()->sum('debit');
        $sumCredit = $this->journalEntryLines()->sum('credit');

        if (in_array($this->type, ['asset', 'expense'])) {
            return $sumDebit - $sumCredit;
        }

        return $sumCredit - $sumDebit;
    }
}
