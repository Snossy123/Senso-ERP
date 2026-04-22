<?php

namespace App\Modules\StorefrontBuilder\Models;

use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Storefront extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'status',
        'active_template_key',
        'published_version_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(StorefrontPage::class);
    }

    public function templateBindings(): HasMany
    {
        return $this->hasMany(StorefrontTemplateBinding::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StorefrontPublishVersion::class);
    }
}
