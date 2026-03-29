<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'tenant_id',
        'type',
        'action',
        'model_type',
        'model_id',
        'description',
        'severity',
        'properties',
        'before_values',
        'after_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties'    => 'array',
        'before_values' => 'array',
        'after_values'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    public static function log(
        string $type, 
        string $action, 
        string $description, 
        array $properties = [], 
        $model = null, 
        string $severity = 'info',
        array $before = null,
        array $after = null
    ): self {
        return self::create([
            'user_id'       => auth()->id(),
            'tenant_id'     => app(\App\Services\TenantManager::class)->getCurrentId(),
            'type'          => $type,
            'action'        => $action,
            'description'   => $description,
            'severity'      => $severity,
            'properties'    => $properties,
            'before_values' => $before,
            'after_values'  => $after,
            'model_type'    => $model ? get_class($model) : null,
            'model_id'      => $model?->id,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);
    }

    public static function logLogin($user): self
    {
        return self::log('auth', 'login', "User '{$user->name}' logged in", [], $user);
    }

    public static function logLogout($user): self
    {
        return self::log('auth', 'logout', "User '{$user->name}' logged out", [], $user);
    }

    public static function logCreated(string $modelType, $model): self
    {
        return self::log('crud', 'create', "Created new {$modelType}", ['id' => $model->id], $model);
    }

    public static function logUpdated(string $modelType, $model, array $changes = []): self
    {
        return self::log('crud', 'update', "Updated {$modelType}", ['id' => $model->id, 'changes' => $changes], $model);
    }

    public static function logDeleted(string $modelType, $model): self
    {
        return self::log('crud', 'delete', "Deleted {$modelType}", ['id' => $model->id ?? null], $model);
    }

    public static function logSale($sale): self
    {
        return self::log('sale', 'create', "New sale {$sale->sale_number} for " . config('app.currency_symbol') . number_format($sale->total, 2), [
            'sale_id' => $sale->id,
            'total' => $sale->total,
        ], $sale);
    }

    public static function logOrder($order): self
    {
        return self::log('order', 'create', "New order {$order->order_number} for " . config('app.currency_symbol') . number_format($order->total, 2), [
            'order_id' => $order->id,
            'total' => $order->total,
        ], $order);
    }
}
