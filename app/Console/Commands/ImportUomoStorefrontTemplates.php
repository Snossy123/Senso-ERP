<?php

namespace App\Console\Commands;

use App\Modules\StorefrontBuilder\Models\Storefront;
use App\Modules\StorefrontBuilder\Models\StorefrontTemplateBinding;
use App\Modules\StorefrontBuilder\Services\StorefrontBuilderService;
use App\Modules\StorefrontBuilder\Services\TemplateRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportUomoStorefrontTemplates extends Command
{
    protected $signature = 'storefront:import-uomo-templates {--slug=default-storefront}';

    protected $description = 'Sync Uomo template metadata (paths/previews) into storefront_template_bindings for the default storefront.';

    public function handle(TemplateRegistryService $registry, StorefrontBuilderService $builder): int
    {
        $slug = (string) $this->option('slug');

        /** @var Storefront|null $storefront */
        $storefront = Storefront::withoutGlobalScopes()->where('slug', $slug)->first();
        if (! $storefront) {
            $this->error("Storefront not found for slug: {$slug}");

            return self::FAILURE;
        }

        $builder->ensureTemplateBindings($storefront->fresh());

        foreach ($registry->allHomeTemplates() as $template) {
            $sourcePath = (string) ($template['source_path'] ?? '');
            $abs = base_path($sourcePath);

            $exists = $sourcePath !== '' && File::isFile($abs);
            $size = $exists ? File::size($abs) : null;

            foreach ($registry->pageTypes() as $pageType) {
                StorefrontTemplateBinding::query()->updateOrCreate(
                    [
                        'storefront_id' => $storefront->id,
                        'template_key' => $template['key'],
                        'page_type' => $pageType,
                    ],
                    [
                        'metadata' => [
                            'source_path' => $sourcePath,
                            'preview_url' => url($registry->previewUrlFromSourcePath($sourcePath)),
                            'source_exists' => $exists,
                            'source_bytes' => $size,
                            'imported_at' => now()->toIso8601String(),
                        ],
                    ]
                );
            }
        }

        $this->info('Uomo template bindings synced.');

        return self::SUCCESS;
    }
}
