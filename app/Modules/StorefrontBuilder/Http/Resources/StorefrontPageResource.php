<?php

namespace App\Modules\StorefrontBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'page_type' => $this->page_type,
            'title' => $this->title,
            'seo' => $this->seo ?? [],
            'layout_schema' => $this->layout_schema,
            'sections' => StorefrontSectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
