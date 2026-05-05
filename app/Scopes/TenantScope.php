<?php

namespace App\Scopes;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->getCurrentId();

        if ($tenantId) {
            $builder->where(function ($query) use ($tenantId, $model) {
                $query->where($model->getTable().'.tenant_id', $tenantId)
                    ->orWhereNull($model->getTable().'.tenant_id');
            });
        }
    }
}
