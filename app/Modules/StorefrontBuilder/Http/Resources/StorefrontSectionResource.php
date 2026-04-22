<?php

namespace App\Modules\StorefrontBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'section_key' => $this->section_key,
            'section_type' => $this->section_type,
            'is_enabled' => $this->is_enabled,
            'sort_order' => $this->sort_order,
            'payload' => $this->payload ?? [],
        ];
    }
}
