<?php

namespace App\Modules\StorefrontBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontSection extends Model
{
    protected $fillable = [
        'storefront_page_id',
        'section_key',
        'section_type',
        'is_enabled',
        'sort_order',
        'payload',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'payload' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(StorefrontPage::class, 'storefront_page_id');
    }
}
