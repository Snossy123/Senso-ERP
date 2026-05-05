<?php

namespace App\Modules\StorefrontBuilder\Services;

class TemplateRegistryService
{
    /**
     * Convert project-relative paths like "uomo/home-10/index.html" to the segment
     * after the top-level /uomo folder, for use with GET /__uomo/{path}.
     */
    public function uomoDiskRelativeFromSourcePath(string $sourcePath): string
    {
        $p = ltrim($sourcePath, '/');
        if (str_starts_with($p, 'uomo/')) {
            return substr($p, strlen('uomo/'));
        }

        return $p;
    }

    public function uomoAssetUrl(string $pathInsideUomoFolder): string
    {
        return '/__uomo/'.ltrim($pathInsideUomoFolder, '/');
    }

    public function previewUrlFromSourcePath(string $sourcePath): string
    {
        return $this->uomoAssetUrl($this->uomoDiskRelativeFromSourcePath($sourcePath));
    }

    /**
     * Static HTML from the purchased Uomo pack that best matches ERP page_type
     * (used for admin visual preview; not the same URL as the Laravel /store routes).
     */
    public function staticReferencePathForPageType(string $pageType, ?string $activeHomeTemplateKey = null): string
    {
        $homeSource = (string) (($meta = $this->findTemplate((string) $activeHomeTemplateKey))
            ? ($meta['source_path'] ?? 'uomo/index.html')
            : 'uomo/index.html');

        return match ($pageType) {
            'home' => $this->uomoDiskRelativeFromSourcePath($homeSource),
            'shop' => 'shop/index.html',
            'product' => 'product/classic-round-cut/index.html',
            'cart' => 'cart/index.html',
            'checkout' => 'checkout/index.html',
            'account' => 'my-account/index.html',
            'wishlist' => 'wishlist/index.html',
            'order-tracking' => 'order-tracking/index.html',
            'login', 'register' => 'my-account/index.html',
            'about' => 'about/index.html',
            'contact' => 'contact/index.html',
            'faq' => 'faq/index.html',
            'terms' => 'terms/index.html',
            default => $this->uomoDiskRelativeFromSourcePath($homeSource),
        };
    }

    public function allHomeTemplates(): array
    {
        $templates = [
            [
                'key' => 'uomo-home-1',
                'name' => 'Uomo Home 1',
                'source_path' => 'uomo/index.html',
                'preview' => $this->previewUrlFromSourcePath('uomo/index.html'),
                'category' => 'home',
            ],
        ];

        for ($i = 2; $i <= 23; $i++) {
            $src = "uomo/home-{$i}/index.html";
            $templates[] = [
                'key' => "uomo-home-{$i}",
                'name' => "Uomo Home {$i}",
                'source_path' => $src,
                'preview' => $this->previewUrlFromSourcePath($src),
                'category' => 'home',
            ];
        }

        return $templates;
    }

    public function pageTypes(): array
    {
        return [
            'home',
            'shop',
            'product',
            'cart',
            'checkout',
            'account',
            'wishlist',
            'order-tracking',
            'login',
            'register',
            'about',
            'contact',
            'faq',
            'terms',
        ];
    }

    public function findTemplate(string $key): ?array
    {
        foreach ($this->allHomeTemplates() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        return null;
    }

    /** @return list<array{key: string, name: string, source_path: string, preview: string, category: string}> */
    public function navbarComponentChoices(): array
    {
        return $this->allHomeTemplates();
    }
}
