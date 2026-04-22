<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UomoAssetController extends Controller
{
    public function show(Request $request, string $path): Response
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $base = realpath(base_path('uomo'));
        if ($base === false) {
            abort(404);
        }

        $full = realpath($base . DIRECTORY_SEPARATOR . $path);
        if ($full === false || !str_starts_with($full, $base)) {
            abort(404);
        }

        if (is_dir($full)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if (in_array($extension, ['html', 'htm'], true)) {
            $contents = @file_get_contents($full);
            if ($contents === false) {
                abort(404);
            }

            $contents = $this->ensureUomoHtmlBaseHref($path, $contents);

            return response($contents, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        $mime = $this->guessMimeType($full);

        return response()->file($full, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * HTTrack / WordPress exports use ../wp-content relative to each folder.
     * A correct <base href="…/"> keeps subpages (e.g. /__uomo/shop/index.html) resolving assets under /__uomo/.
     */
    private function ensureUomoHtmlBaseHref(string $requestPath, string $html): string
    {
        $href = $this->uomoHtmlBaseHref($requestPath);
        $escaped = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if (stripos($html, '<base') !== false) {
            return preg_replace(
                '/<base\b[^>]*>/i',
                '<base href="' . $escaped . '">',
                $html,
                1
            ) ?? $html;
        }

        if (preg_match('/<head\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $tag = $m[0][0];
            $pos = $m[0][1] + strlen($tag);

            return substr($html, 0, $pos) . "\n<base href=\"{$escaped}\">" . substr($html, $pos);
        }

        return "<!DOCTYPE html>\n<html><head><base href=\"{$escaped}\"></head><body>\n" . $html . "\n</body></html>\n";
    }

    private function uomoHtmlBaseHref(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $dir = dirname($normalized);
        if ($dir === '.' || $dir === '') {
            return rtrim(url('/__uomo/'), '/') . '/';
        }

        return rtrim(url('/__uomo/' . trim($dir, '/')), '/') . '/';
    }

    private function guessMimeType(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'map' => 'application/json; charset=UTF-8',
            'xml' => 'application/xml; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'html', 'htm' => 'text/html; charset=UTF-8',
            default => @mime_content_type($absolutePath) ?: 'application/octet-stream',
        };
    }
}
