<?php

namespace App\Modules\StorefrontBuilder\Services;

use App\Modules\StorefrontBuilder\Models\Storefront;
use App\Modules\StorefrontBuilder\Models\StorefrontPage;
use App\Modules\StorefrontBuilder\Models\StorefrontPublishVersion;
use App\Modules\StorefrontBuilder\Models\StorefrontSection;
use App\Modules\StorefrontBuilder\Models\StorefrontTemplateBinding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorefrontBuilderService
{
    public function __construct(
        private readonly TemplateRegistryService $templateRegistry,
        private readonly LayoutSlotService $layoutSlotService,
        private readonly PageSchemaService $pageSchemaService
    ) {
    }

    public function getOrCreateDefaultStorefront(): Storefront
    {
        $storefront = Storefront::firstOrCreate(
            ['slug' => 'default-storefront'],
            [
                'name' => 'Default Storefront',
                'status' => 'draft',
                'settings' => ['theme' => 'uomo', 'beta_mode' => true],
                'active_template_key' => 'uomo-home-1',
            ]
        );

        $this->ensureCorePages($storefront);
        $this->ensureTemplateBindings($storefront);
        $this->layoutSlotService->ensureLayoutSlotsOnStorefront($storefront->fresh());

        return $storefront->fresh(['pages.sections', 'templateBindings', 'versions']);
    }

    public function ensureCorePages(Storefront $storefront): void
    {
        $defaults = [
            'home' => ['hero', 'featured_categories', 'featured_products', 'cta', 'footer'],
            'shop' => ['hero', 'filters', 'product_grid', 'footer'],
            'product' => ['breadcrumbs', 'product_details', 'related_products', 'footer'],
            'cart' => ['hero', 'cart_table', 'cart_summary', 'footer'],
            'checkout' => ['hero', 'checkout_form', 'order_summary', 'footer'],
            'account' => ['hero', 'account_navigation', 'account_content', 'footer'],
            'wishlist' => ['hero', 'wishlist_grid', 'footer'],
            'order-tracking' => ['hero', 'tracking_form', 'footer'],
            'login' => ['hero', 'auth_form', 'footer'],
            'register' => ['hero', 'auth_form', 'footer'],
            'about' => ['hero', 'content', 'footer'],
            'contact' => ['hero', 'contact_form', 'footer'],
            'faq' => ['hero', 'faq_list', 'footer'],
            'terms' => ['hero', 'content', 'footer'],
        ];

        foreach ($defaults as $pageType => $sections) {
            $page = StorefrontPage::firstOrCreate(
                ['storefront_id' => $storefront->id, 'page_type' => $pageType],
                ['title' => Str::title(str_replace('-', ' ', $pageType))]
            );

            foreach ($sections as $idx => $sectionType) {
                StorefrontSection::firstOrCreate(
                    ['storefront_page_id' => $page->id, 'section_key' => "{$pageType}-{$sectionType}"],
                    [
                        'section_type' => $sectionType,
                        'sort_order' => $idx + 1,
                        'is_enabled' => true,
                        'payload' => $this->defaultSectionPayload($pageType, $sectionType),
                    ]
                );
            }
        }
    }

    public function ensureTemplateBindings(Storefront $storefront): void
    {
        foreach ($this->templateRegistry->allHomeTemplates() as $template) {
            foreach ($this->templateRegistry->pageTypes() as $pageType) {
                StorefrontTemplateBinding::firstOrCreate(
                    [
                        'storefront_id' => $storefront->id,
                        'template_key' => $template['key'],
                        'page_type' => $pageType,
                    ],
                    [
                        'metadata' => [
                            'source_path' => $template['source_path'],
                            'preview' => $template['preview'],
                            'compatible' => true,
                        ],
                    ]
                );
            }
        }
    }

    public function updatePageSections(Storefront $storefront, string $pageType, array $sections): Storefront
    {
        $page = $storefront->pages()->where('page_type', $pageType)->firstOrFail();

        foreach ($sections as $idx => $sectionData) {
            $section = StorefrontSection::where('storefront_page_id', $page->id)
                ->where('section_key', $sectionData['section_key'])
                ->first();

            if (!$section) {
                continue;
            }

            $section->update([
                'sort_order' => isset($sectionData['sort_order'])
                    ? (int) $sectionData['sort_order']
                    : ($idx + 1),
                'is_enabled' => (bool) ($sectionData['is_enabled'] ?? true),
                'payload' => $sectionData['payload'] ?? $section->payload,
            ]);
        }

        $storefront->update(['status' => 'draft']);

        $this->forgetRenderCache($storefront, null);

        return $storefront->fresh(['pages.sections']);
    }

    public function publish(Storefront $storefront, ?int $publisherId = null): StorefrontPublishVersion
    {
        $errors = $this->validatePublishReadiness($storefront);
        if ($errors !== []) {
            throw new \RuntimeException(implode(' ', $errors));
        }

        /** @var StorefrontPublishVersion $published */
        $published = DB::transaction(function () use ($storefront, $publisherId) {
            $oldPublishedVersionId = $storefront->published_version_id;
            $nextVersion = (int) $storefront->versions()->max('version') + 1;

            $pages = $storefront->pages()->with('sections')->get();
            $layoutDigest = hash('sha256', $pages->map(fn(StorefrontPage $p) => json_encode($p->layout_schema))->implode('|'));

            $snapshot = [
                'storefront' => $storefront->only(['id', 'name', 'slug', 'active_template_key', 'settings']),
                'studio_meta' => [
                    'schema_version' => PageSchemaService::SCHEMA_VERSION,
                    'layout_digest' => $layoutDigest,
                    'published_at' => now()->toIso8601String(),
                ],
                'pages' => $pages->map(function (StorefrontPage $page) {
                    return [
                        'page_type' => $page->page_type,
                        'title' => $page->title,
                        'layout_schema' => $page->layout_schema,
                        'sections' => $page->sections->map(fn(StorefrontSection $section) => [
                            'section_key' => $section->section_key,
                            'section_type' => $section->section_type,
                            'is_enabled' => $section->is_enabled,
                            'sort_order' => $section->sort_order,
                            'payload' => $section->payload,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ];

            $version = StorefrontPublishVersion::create([
                'storefront_id' => $storefront->id,
                'version' => $nextVersion,
                'snapshot' => $snapshot,
                'published_by' => $publisherId,
                'published_at' => now(),
            ]);

            $storefront->update([
                'status' => 'published',
                'published_version_id' => $version->id,
            ]);

            $this->forgetRenderCache($storefront->fresh(), $oldPublishedVersionId);

            return $version;
        });

        return $published;
    }

    public function validatePublishReadiness(Storefront $storefront): array
    {
        $requiredPages = ['home', 'shop', 'product', 'cart', 'checkout', 'account'];
        $errors = [];

        foreach ($requiredPages as $pageType) {
            $page = $storefront->pages()->where('page_type', $pageType)->with('sections')->first();
            if (!$page) {
                $errors[] = "Missing page schema for {$pageType}.";
                continue;
            }

            if ($page->sections->where('is_enabled', true)->isEmpty()) {
                $errors[] = "No enabled sections for {$pageType}.";
            }
        }

        if (!$this->templateRegistry->findTemplate((string) $storefront->active_template_key)) {
            $errors[] = 'Selected template is not in Uomo registry.';
        }

        return $errors;
    }

    public function rollback(Storefront $storefront, int $version): Storefront
    {
        $target = $storefront->versions()->where('version', $version)->firstOrFail();
        $oldPublishedVersionId = $storefront->published_version_id;

        $storefront->update([
            'status' => 'published',
            'published_version_id' => $target->id,
            'active_template_key' => data_get($target->snapshot, 'storefront.active_template_key', $storefront->active_template_key),
            'settings' => data_get($target->snapshot, 'storefront.settings', $storefront->settings),
        ]);

        $this->forgetRenderCache($storefront->fresh(), $oldPublishedVersionId);

        return $storefront->fresh(['pages.sections', 'versions']);
    }

    public function forgetRenderCache(Storefront $storefront, ?int $oldPublishedVersionId = null): void
    {
        $tenantId = (int) $storefront->tenant_id;
        $storefrontId = (int) $storefront->id;

        $publishedVersionId = (int) ($storefront->published_version_id ?? 0);
        $oldPublishedVersionId = (int) ($oldPublishedVersionId ?? 0);

        foreach ($this->templateRegistry->pageTypes() as $pageType) {
            Cache::forget("storefront-render:{$tenantId}:{$storefrontId}:{$publishedVersionId}:{$pageType}");
        }

        // Also clear "no published version" cache bucket used before first publish.
        foreach ($this->templateRegistry->pageTypes() as $pageType) {
            Cache::forget("storefront-render:{$tenantId}:{$storefrontId}:0:{$pageType}");
        }

        if ($oldPublishedVersionId > 0 && $oldPublishedVersionId !== $publishedVersionId) {
            foreach ($this->templateRegistry->pageTypes() as $pageType) {
                Cache::forget("storefront-render:{$tenantId}:{$storefrontId}:{$oldPublishedVersionId}:{$pageType}");
            }
        }
    }

    private function defaultSectionPayload(string $pageType, string $sectionType): array
    {
        return match ($sectionType) {
            'hero' => [
                'title' => Str::title(str_replace('-', ' ', $pageType)),
                'subtitle' => 'Build and publish your storefront from ERP.',
            ],
            'cta' => [
                'label' => 'Start Shopping',
                'url' => '/store',
            ],
            default => [],
        };
    }
}
