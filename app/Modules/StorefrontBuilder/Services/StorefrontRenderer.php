<?php

namespace App\Modules\StorefrontBuilder\Services;

use App\Modules\StorefrontBuilder\Models\Storefront;
use App\Modules\StorefrontBuilder\Models\StorefrontPage;
use App\Modules\StorefrontBuilder\Models\StorefrontPublishVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StorefrontRenderer
{
    public function __construct(
        private readonly StorefrontBuilderService $builderService,
        private readonly PageSchemaService $pageSchemaService
    ) {
    }

    public function forPage(string $pageType): array
    {
        if (!Schema::hasTable('storefronts')) {
            return $this->fallback(null, null, []);
        }

        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $cacheKey = "storefront-render:{$storefront->tenant_id}:{$storefront->id}:{$storefront->published_version_id}:{$pageType}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($storefront, $pageType) {
            $published = null;
            if ($storefront->published_version_id) {
                $published = StorefrontPublishVersion::query()
                    ->where('storefront_id', $storefront->id)
                    ->where('id', $storefront->published_version_id)
                    ->first();
            }

            $builderSettings = $this->builderSettingsFromSource($storefront, $published);

            if ($published && $storefront->published_version_id) {
                $snapshotPage = collect(data_get($published->snapshot, 'pages', []))
                    ->firstWhere('page_type', $pageType);

                if (is_array($snapshotPage)) {
                    $sections = collect(data_get($snapshotPage, 'sections', []))
                        ->where('is_enabled', true)
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn($section) => [
                            'section_key' => $section['section_key'] ?? null,
                            'section_type' => $section['section_type'] ?? null,
                            'payload' => $section['payload'] ?? [],
                        ])
                        ->filter(fn($section) => $section['section_key'] && $section['section_type'])
                        ->values()
                        ->all();

                    return [
                        'storefront' => $storefront,
                        'template_key' => data_get($published->snapshot, 'storefront.active_template_key', $storefront->active_template_key),
                        'page_type' => $pageType,
                        'title' => $snapshotPage['title'] ?? null,
                        'sections' => $sections,
                        'layout_schema' => data_get($snapshotPage, 'layout_schema'),
                        'studio_meta' => data_get($published->snapshot, 'studio_meta'),
                        'published_version_id' => $storefront->published_version_id,
                        'source' => 'published_snapshot',
                        'builder_settings' => $builderSettings,
                    ];
                }
            }

            $page = $storefront->pages
                ->firstWhere('page_type', $pageType);

            if (!$page instanceof StorefrontPage) {
                return $this->fallback($storefront->active_template_key, $pageType, $builderSettings);
            }

            $sections = $page->sections
                ->where('is_enabled', true)
                ->sortBy('sort_order')
                ->values()
                ->map(fn($section) => [
                    'section_key' => $section->section_key,
                    'section_type' => $section->section_type,
                    'payload' => $section->payload ?? [],
                ])
                ->all();

            return [
                'storefront' => $storefront,
                'template_key' => $storefront->active_template_key,
                'page_type' => $pageType,
                'title' => $page->title,
                'sections' => $sections,
                'layout_schema' => $this->pageSchemaService->resolvedSchema($page),
                'studio_meta' => null,
                'published_version_id' => $storefront->published_version_id,
                'source' => $storefront->published_version_id ? 'published_snapshot_missing' : 'draft_tables',
                'builder_settings' => $builderSettings,
            ];
        });
    }

    /**
     * Settings used for layout slots: published snapshot when available, else live storefront.
     *
     * @return array<string, mixed>
     */
    private function builderSettingsFromSource(Storefront $storefront, ?StorefrontPublishVersion $published): array
    {
        if ($published && $storefront->published_version_id === $published->id) {
            return (array) data_get($published->snapshot, 'storefront.settings', []);
        }

        return (array) ($storefront->settings ?? []);
    }

    private function fallback(?string $templateKey, ?string $pageType = null, array $builderSettings = []): array
    {
        return [
            'storefront' => null,
            'template_key' => $templateKey,
            'page_type' => $pageType,
            'title' => null,
            'sections' => [],
            'layout_schema' => null,
            'studio_meta' => null,
            'published_version_id' => null,
            'builder_settings' => $builderSettings,
        ];
    }
}
