<?php

namespace App\Services;

class PdfAssetService
{
    public function localImageUri(?string $source): ?string
    {
        $source = trim((string) $source);
        if ($source === '') return null;
        $parts = parse_url($source);
        if ($parts === false || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) ||
            (isset($parts['host']) && strcasecmp($parts['host'], (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0)) {
            return null;
        }
        $path = rawurldecode((string) ($parts['path'] ?? ''));
        if (str_contains($path, "\0")) return null;
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $candidates = [public_path($relative)];
        // Common Laravel uploads may be stored on the public disk without a
        // public/storage symlink, so try that trusted local disk as well.
        if (str_starts_with($relative, 'storage/')) {
            $candidates[] = storage_path('app/public/' . substr($relative, strlen('storage/')));
        } else {
            $candidates[] = storage_path('app/public/' . $relative);
        }
        $file = null;
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved && is_file($resolved)) {
                $file = $resolved;
                break;
            }
        }
        $roots = array_filter([realpath(public_path()), realpath(storage_path('app/public'))]);
        $allowed = $file && is_file($file) && collect($roots)->contains(fn ($root) =>
            str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR));
        if (! $allowed || filesize($file) > 5_000_000 || ! in_array(mime_content_type($file), ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return null;
        }

        // DomPDF strips file:// before realpath(); an extra slash breaks Windows drive paths.
        return 'file://' . str_replace('\\', '/', $file);
    }

    public function localizeImages(string $html): string
    {
        // DomPDF does not resolve browser-root /uploads URLs against public_path.
        // Only verified local raster images may be embedded; no remote fetching.
        return preg_replace_callback('/(<img\b[^>]*\bsrc=)(["\'])(.*?)\2/i', function ($matches) {
            $source = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return $matches[1] . '"' . e($this->localImageUri($source) ?? '') . '"';
        }, $html) ?? $html;
    }
}
