<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'account_id',
    ];

    /**
     * The GL account mapped to this setting.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Static helper to get account ID for a specific key.
     */
    public static function getAccountId(string $key, $tenantId = null): ?int
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        
        return self::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->value('account_id');
    }

    /**
     * Static helper to get the full Account model for a specific key.
     */
    public static function getAccount(string $key, $tenantId = null): ?Account
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        
        $setting = self::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->with('account')
            ->first();

        return $setting ? $setting->account : null;
    }
}
