<?php

namespace App\Modules\StorefrontBuilder\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontPublishVersion extends Model
{
    protected $fillable = [
        'storefront_id',
        'version',
        'status',
        'snapshot',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'published_at' => 'datetime',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(Storefront::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
