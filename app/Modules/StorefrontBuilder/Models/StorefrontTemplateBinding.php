<?php

namespace App\Modules\StorefrontBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontTemplateBinding extends Model
{
    protected $fillable = [
        'storefront_id',
        'template_key',
        'page_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(Storefront::class);
    }
}
