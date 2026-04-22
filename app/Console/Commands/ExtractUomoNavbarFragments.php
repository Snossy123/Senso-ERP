<?php

namespace App\Console\Commands;

use App\Modules\StorefrontBuilder\Services\TemplateRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExtractUomoNavbarFragments extends Command
{
    protected $signature = 'storefront:extract-uomo-navbar-fragments
        {--output=storage/app/uomo-fragments : Directory under base_path to write HTML fragments}';

    protected $description = 'Extract <header>...</header> (or first large header block) from each Uomo home HTML into fragment files for Phase 3B Blade includes.';

    public function handle(TemplateRegistryService $registry): int
    {
        $relativeOut = trim((string) $this->option('output'), '/');
        $outDir = base_path($relativeOut);
        if (!File::isDirectory($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $manifest = [];
        foreach ($registry->allHomeTemplates() as $template) {
            $src = base_path((string) $template['source_path']);
            if (!is_file($src)) {
                $this->warn("Missing source: {$template['source_path']}");
                continue;
            }

            $html = (string) file_get_contents($src);
            $fragment = $this->extractHeaderFragment($html);
            $key = (string) $template['key'];
            $file = $outDir . DIRECTORY_SEPARATOR . "navbar-{$key}.html";
            File::put($file, $fragment);
            $manifest[$key] = [
                'bytes' => strlen($fragment),
                'source_path' => $template['source_path'],
                'fragment_relative' => $relativeOut . '/navbar-' . $key . '.html',
            ];
            $this->line("Wrote {$file}");
        }

        File::put($outDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Done. Point Blade includes at these files or copy into resources/views/store/uomo/fragments/ as .blade.php after sanitizing URLs.');

        return self::SUCCESS;
    }

    private function extractHeaderFragment(string $html): string
    {
        if (preg_match('/<header\b[^>]*>.*?<\/header>/is', $html, $m)) {
            return trim($m[0]);
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if ($loaded) {
            $xpath = new \DOMXPath($dom);
            $query = '//*[contains(concat(" ", normalize-space(@class), " "), " header-desktop ")'
                . ' or contains(concat(" ", normalize-space(@class), " "), " apus-header ")'
                . ' or contains(concat(" ", normalize-space(@class), " "), " site-header ")]';
            $node = $xpath->query($query)?->item(0);
            if ($node instanceof \DOMNode) {
                return trim((string) $dom->saveHTML($node));
            }
        }

        return "<!-- storefront:extract-uomo-navbar-fragments: no <header> match; refine selectors for this file -->\n";
    }
}
