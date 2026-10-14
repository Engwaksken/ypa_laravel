<?php

namespace Tests\Unit;

use App\Services\PdfAssetService;
use Tests\TestCase;

class PdfAssetServiceTest extends TestCase
{
    public function test_remote_files_local_protocols_and_path_traversal_are_not_embedded(): void
    {
        $service = new PdfAssetService;
        foreach (['https://external.test/logo.png', 'file:///etc/passwd', '/../../.env', '/missing.png', '/%00.png'] as $source) {
            $this->assertSame('<img src="" alt="Logo">', $service->localizeImages('<img src="' . $source . '" alt="Logo">'));
        }
    }

    public function test_verified_public_images_resolve_to_local_files_without_http_requests(): void
    {
        $name = 'pdf-test-' . bin2hex(random_bytes(6)) . '.png';
        $file = public_path($name);
        file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII='));
        try {
            $service = new PdfAssetService;
            $html = $service->localizeImages('<img src="/' . $name . '">');
            $this->assertStringContainsString('src="file:///', $html);
            $this->assertStringContainsString($name, $html);
            $this->assertSame($html, $service->localizeImages('<img src="' . rtrim(config('app.url'), '/') . '/' . $name . '">'));
        } finally {
            unlink($file);
        }
    }
}
