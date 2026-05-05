<?php

namespace App\Modules\StorefrontBuilder\Services;

use App\Modules\StorefrontBuilder\Models\Storefront;

class LayoutSlotService
{
    public function __construct(private readonly TemplateRegistryService $templates) {}

    /** @return list<string> */
    public function allowedHomeTemplateKeys(): array
    {
        return array_values(array_map(
            static fn (array $t) => (string) $t['key'],
            $this->templates->allHomeTemplates()
        ));
    }

    /**
     * Deep-merge layout slot trees (global + pages.*) without clobbering sibling keys.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    public function mergeLayoutSlots(array $base, array $patch): array
    {
        $out = $base;
        foreach ($patch as $key => $value) {
            if (is_array($value) && isset($out[$key]) && is_array($out[$key])) {
                $out[$key] = $this->mergeLayoutSlots($out[$key], $value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function globalNavbarKey(?array $settings): ?string
    {
        $key = data_get($settings, 'layout_slots.global.navbar');
        if ($key === null || $key === '') {
            return null;
        }

        return $this->templates->findTemplate((string) $key) ? (string) $key : null;
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function pageSlot(?array $settings, string $pageType, string $slot): ?string
    {
        $key = data_get($settings, "layout_slots.pages.{$pageType}.{$slot}");
        if ($key === null || $key === '') {
            return null;
        }

        return $this->templates->findTemplate((string) $key) ? (string) $key : null;
    }

    /**
     * Default structure for new storefronts / migrations in code.
     *
     * @return array<string, mixed>
     */
    public function defaultLayoutSlotsStructure(): array
    {
        return [
            'global' => [
                'navbar' => null,
            ],
            'pages' => [
                'home' => [
                    'hero' => null,
                    'footer' => null,
                ],
            ],
        ];
    }

    /**
     * Merge incoming full settings array with existing, applying layout_slots deep merge.
     *
     * @param  array<string, mixed>  $existingSettings
     * @param  array<string, mixed>  $incomingSettings
     * @return array<string, mixed>
     */
    public function mergeStorefrontSettings(array $existingSettings, array $incomingSettings): array
    {
        $merged = array_merge($existingSettings, $incomingSettings);

        if (array_key_exists('layout_slots', $incomingSettings)) {
            $merged['layout_slots'] = $this->mergeLayoutSlots(
                (array) data_get($existingSettings, 'layout_slots', $this->defaultLayoutSlotsStructure()),
                (array) ($incomingSettings['layout_slots'] ?? [])
            );
        }

        return $merged;
    }

    public function ensureLayoutSlotsOnStorefront(Storefront $storefront): void
    {
        $settings = $storefront->settings ?? [];
        if (! isset($settings['layout_slots']) || ! is_array($settings['layout_slots'])) {
            $settings['layout_slots'] = $this->mergeLayoutSlots(
                $this->defaultLayoutSlotsStructure(),
                []
            );
            $storefront->update(['settings' => $settings]);
        }
    }
}
