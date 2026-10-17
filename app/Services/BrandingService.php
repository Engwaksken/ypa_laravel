<?php

namespace App\Services;

use App\Models\Setting;

/**
 * One source for the organisation name and logo used by emails, PDFs and
 * printable pages, falling back to the bundled logo when none is configured.
 */
class BrandingService
{
    public const DEFAULT_LOGO = 'images/logo.png';

    public function name(): string
    {
        try {
            return Setting::value('site_name', 'Youth Platform Africa') ?: 'Youth Platform Africa';
        } catch (\Throwable) {
            return 'Youth Platform Africa';
        }
    }

    /** Public-relative path of the configured logo, or the bundled default. */
    public function logoPath(): string
    {
        try {
            $path = Setting::asset('site_logo', '');
        } catch (\Throwable) {
            $path = '';
        }

        return $path !== '' && $this->localFile($path) ? $path : self::DEFAULT_LOGO;
    }

    public function logoUrl(): string
    {
        return asset($this->logoPath());
    }

    /** file:// URI DomPDF can embed without remote access. */
    public function pdfLogo(): ?string
    {
        return app(PdfAssetService::class)->localImageUri($this->logoPath())
            ?? app(PdfAssetService::class)->localImageUri(self::DEFAULT_LOGO);
    }

    /** Absolute path for embedding the logo inline in emails (CID attachment). */
    public function emailLogoFile(): ?string
    {
        return $this->localFile($this->logoPath()) ?? $this->localFile(self::DEFAULT_LOGO);
    }

    private function localFile(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        foreach ([public_path($relative), storage_path('app/public/' . preg_replace('#^storage/#', '', $relative))] as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }
}
