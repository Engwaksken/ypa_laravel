<?php

namespace App\Services;

class PdfAssetService
{
    public function localizeImages(string $html): string
    {
        // DomPDF does not resolve browser-root /uploads URLs against public_path.
        // Only verified local raster images may be embedded; no remote fetching.
        return preg_replace_callback('/(<img\b[^>]*\bsrc=)(["\'])(.*?)\2/i', function ($matches) {
            $source = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $parts = parse_url($source);
            if ($parts === false || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) ||
                (isset($parts['host']) && strcasecmp($parts['host'], (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0)) {
                return $matches[1] . '""';
            }
            $path = rawurldecode((string) ($parts['path'] ?? ''));
            if (str_contains($path, "\0")) return $matches[1] . '""';
            $file = realpath(public_path(ltrim(str_replace('\\', '/', $path), '/')));
            $roots = array_filter([realpath(public_path()), realpath(storage_path('app/public'))]);
            $allowed = $file && is_file($file) && collect($roots)->contains(fn ($root) =>
                str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR));
            if (! $allowed || ! in_array(mime_content_type($file), ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
                return $matches[1] . '""';
            }

            return $matches[1] . '"' . e('file:///' . ltrim(str_replace('\\', '/', $file), '/')) . '"';
        }, $html) ?? $html;
    }
}
