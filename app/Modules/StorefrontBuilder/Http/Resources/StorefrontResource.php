<?php

namespace App\Modules\StorefrontBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $publishedVersion = $this->versions->firstWhere('id', $this->published_version_id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'active_template_key' => $this->active_template_key,
            'settings' => $this->settings ?? [],
            'published_version_id' => $this->published_version_id,
            'pages' => StorefrontPageResource::collection($this->whenLoaded('pages')),
            'published_payload' => $publishedVersion?->snapshot ?? null,
        ];
    }
}
