<?php

namespace App\Modules\StorefrontBuilder\Services;

use App\Modules\StorefrontBuilder\Models\StorefrontPage;
use App\Modules\StorefrontBuilder\Models\StorefrontSection;
use Illuminate\Support\Str;

/**
 * Page Schema v2: declarative tree (nodes + bindings) for Visual Store Studio.
 * Syncs with legacy {@see StorefrontSection} rows for backward compatibility.
 */
class PageSchemaService
{
    public const SCHEMA_VERSION = 2;

    /**
     * @return array<string, mixed>
     */
    public function emptyRootSchema(): array
    {
        return [
            'version' => self::SCHEMA_VERSION,
            'meta' => [
                'studio' => 'erp-visual-store',
            ],
            'root' => [
                'id' => 'root',
                'type' => 'root',
                'props' => [],
                'bindings' => [],
                'children' => [],
            ],
        ];
    }

    /**
     * Build v2 schema from ordered sections (read path when layout_schema is null).
     *
     * @return array<string, mixed>
     */
    public function schemaFromSections(StorefrontPage $page): array
    {
        $schema = $this->emptyRootSchema();
        $children = [];

        foreach ($page->sections()->orderBy('sort_order')->get() as $section) {
            $children[] = $this->sectionToNode($section);
        }

        $schema['root']['children'] = $children;

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedSchema(StorefrontPage $page): array
    {
        $raw = $page->layout_schema;
        if (is_array($raw) && isset($raw['root'], $raw['version'])) {
            return $raw;
        }

        return $this->schemaFromSections($page);
    }

    /**
     * Persist schema and mirror critical nodes into sections (hero/cta text) when possible.
     *
     * @param  array<string, mixed>  $schema
     */
    public function saveSchema(StorefrontPage $page, array $schema): void
    {
        $page->update(['layout_schema' => $schema]);
        $this->syncSectionsFromSchemaNodes($page, (array) data_get($schema, 'root.children', []));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function syncSectionsFromSchemaNodes(StorefrontPage $page, array $nodes): void
    {
        foreach ($nodes as $node) {
            $type = (string) ($node['type'] ?? '');
            $sectionKey = (string) ($node['props']['section_key'] ?? '');
            if ($sectionKey === '' || ! str_contains($sectionKey, '-')) {
                continue;
            }

            $section = StorefrontSection::query()
                ->where('storefront_page_id', $page->id)
                ->where('section_key', $sectionKey)
                ->first();

            if (! $section) {
                continue;
            }

            if ($type === 'hero' && isset($node['props']['title'], $node['props']['subtitle'])) {
                $payload = $section->payload ?? [];
                $payload['title'] = (string) $node['props']['title'];
                $payload['subtitle'] = (string) $node['props']['subtitle'];
                $section->update(['payload' => $payload]);
            }

            if ($type === 'cta' && isset($node['props']['label'], $node['props']['url'])) {
                $payload = $section->payload ?? [];
                $payload['label'] = (string) $node['props']['label'];
                $payload['url'] = (string) $node['props']['url'];
                $section->update(['payload' => $payload]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionToNode(StorefrontSection $section): array
    {
        $type = $this->nodeTypeForSectionType($section->section_type);
        $bindings = $this->defaultBindingsForSection($section);

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'props' => array_merge([
                'section_key' => $section->section_key,
                'section_type' => $section->section_type,
                'sort_order' => $section->sort_order,
                'is_enabled' => $section->is_enabled,
            ], $section->payload ?? []),
            'bindings' => $bindings,
            'children' => [],
        ];
    }

    private function nodeTypeForSectionType(string $sectionType): string
    {
        return match ($sectionType) {
            'hero' => 'hero',
            'cta' => 'cta',
            'product_grid' => 'product_grid',
            'filters' => 'filters',
            'footer' => 'footer',
            default => 'block',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultBindingsForSection(StorefrontSection $section): array
    {
        return match ($section->section_type) {
            'product_grid' => [
                'source' => 'erp.products.list',
                'params' => [
                    'tenant' => true,
                    'ecommerce_only' => true,
                ],
            ],
            'filters' => [
                'source' => 'erp.categories.tree',
                'params' => ['tenant' => true],
            ],
            default => [],
        };
    }
}
