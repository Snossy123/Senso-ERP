<?php

namespace App\Modules\StorefrontBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\StorefrontBuilder\Http\Resources\StorefrontResource;
use App\Modules\StorefrontBuilder\Models\Storefront;
use App\Modules\StorefrontBuilder\Models\StorefrontPublishVersion;
use App\Modules\StorefrontBuilder\Services\StorefrontBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontBuilderApiController extends Controller
{
    public function __construct(private readonly StorefrontBuilderService $builderService) {}

    public function show(): JsonResource
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();

        return new StorefrontResource($storefront->load('pages.sections', 'versions'));
    }

    public function publishedPayload(): JsonResource
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $storefront->load('versions');

        return new StorefrontResource($storefront);
    }

    public function boot(Request $request): JsonResponse
    {
        $slug = (string) $request->query('slug', 'default-storefront');

        /** @var Storefront|null $storefront */
        $storefront = Storefront::query()->where('slug', $slug)->first();
        if (! $storefront) {
            return response()->json(['message' => 'Storefront not found.'], 404);
        }

        /** @var StorefrontPublishVersion|null $published */
        $published = null;
        if ($storefront->published_version_id) {
            $published = StorefrontPublishVersion::query()
                ->where('storefront_id', $storefront->id)
                ->where('id', $storefront->published_version_id)
                ->first();
        }

        $snapshot = $published && is_array($published->snapshot) ? $published->snapshot : null;

        return response()->json([
            'storefront' => [
                'id' => $storefront->id,
                'slug' => $storefront->slug,
                'name' => $storefront->name,
                'status' => $storefront->status,
                'active_template_key' => $storefront->active_template_key,
                'settings' => $storefront->settings ?? [],
                'published_version_id' => $storefront->published_version_id,
            ],
            'published' => $published ? [
                'id' => $published->id,
                'version' => $published->version,
                'published_at' => optional($published->published_at)?->toIso8601String(),
                'studio_meta' => $snapshot['studio_meta'] ?? null,
                'snapshot' => $published->snapshot,
            ] : null,
        ]);
    }
}
