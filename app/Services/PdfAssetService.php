<?php

namespace App\Services;

class PdfAssetService
{
    public function localImageUri(?string $source): ?string
    {
        $file = $this->localImageFile($source);
        return $file ? 'file://' . str_replace('\\', '/', $file) : null;
    }

    /** Embed verified local photos in browser printing without requiring a storage symlink. */
    public function browserImageUri(?string $source): ?string
    {
        $file = $this->localImageFile($source);
        return $file ? 'data:' . mime_content_type($file) . ';base64,' . base64_encode(file_get_contents($file)) : null;
    }

    private function localImageFile(?string $source): ?string
    {
        $source = trim((string) $source);
        if ($source === '') return null;
        $source = str_replace('\\', '/', $source);
        $parts = parse_url($source);
        $hosts = array_filter([(string) parse_url(config('app.url'), PHP_URL_HOST), ...config('contract_assets.legacy_hosts', [])]);
        if ($parts === false || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) ||
            (isset($parts['host']) && ! in_array(strtolower($parts['host']), array_map('strtolower', $hosts), true))) {
            return null;
        }
        $path = rawurldecode((string) ($parts['path'] ?? ''));
        if (str_contains($path, "\0")) return null;
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        // Legacy rows commonly store ../uploads/... relative to their PHP page.
        $relative = preg_replace('#^(?:\./|\.\./)+(?=uploads/)#', '', $relative);
        $relative = preg_replace('#^public/#', '', $relative);
        if (in_array('..', explode('/', $relative), true)) return null;
        $publicDisk = config('filesystems.disks.public.root', storage_path('app/public'));
        $candidates = [
            [public_path(), $relative],
            [$publicDisk, preg_replace('#^storage/#', '', $relative)],
        ];
        $legacyRoot = config('contract_assets.legacy_uploads_path');
        if ($legacyRoot && str_starts_with($relative, 'uploads/')) {
            $candidates[] = [$legacyRoot, substr($relative, strlen('uploads/'))];
        }
        foreach ($candidates as [$root, $relativePath]) {
            $root = realpath($root);
            if (!$root) continue;
            $file = realpath($root . DIRECTORY_SEPARATOR . $relativePath);
            // Symlinks may only target an explicitly trusted image root.
            $roots = array_filter([realpath(public_path()), realpath($publicDisk), $legacyRoot ? realpath($legacyRoot) : false]);
            $allowed = $file && is_file($file) && collect($roots)->contains(fn ($trusted) =>
                str_starts_with($file, rtrim($trusted, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR));
            if (!$allowed || filesize($file) > 5_000_000 || !in_array(mime_content_type($file), ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) continue;
            return $file;
        }
        return null;
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

    public function browserImages(string $html): string
    {
        return preg_replace_callback('/(<img\b[^>]*\bsrc=)(["\'])(.*?)\2/i', function ($matches) {
            $source = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return $matches[1] . '"' . e($this->browserImageUri($source) ?? '') . '"';
        }, $html) ?? $html;
    }
}
