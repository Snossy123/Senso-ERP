<?php

namespace App\Modules\StorefrontBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorefrontPage extends Model
{
    protected $fillable = [
        'storefront_id',
        'page_type',
        'title',
        'seo',
        'layout_schema',
        'sort_order',
    ];

    protected $casts = [
        'seo' => 'array',
        'layout_schema' => 'array',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(Storefront::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(StorefrontSection::class)->orderBy('sort_order');
    }
}
