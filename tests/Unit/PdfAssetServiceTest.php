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
            $this->assertStringContainsString('src="file://', $html);
            $this->assertStringContainsString($name, $html);
            $this->assertSame($html, $service->localizeImages('<img src="' . rtrim(config('app.url'), '/') . '/' . $name . '">'));
            $this->assertNotNull(\Dompdf\Helpers::build_url('file://', '', public_path(), $service->localImageUri('/' . $name), [public_path()]));
        } finally {
            unlink($file);
        }
    }

    public function test_legacy_paths_and_hosts_map_only_to_trusted_local_uploads(): void
    {
        $directory = storage_path('framework/cache/legacy-photo-test-' . bin2hex(random_bytes(6)));
        mkdir($directory);
        copy(public_path('images/logo.png'), $directory . '/photo.png');
        config(['contract_assets.legacy_uploads_path' => $directory, 'contract_assets.legacy_hosts' => ['old.example.test']]);
        try {
            $service = new PdfAssetService;
            foreach (['../uploads/photo.png', './uploads/photo.png', 'uploads\\photo.png', 'https://old.example.test/uploads/photo.png'] as $source) {
                $this->assertStringStartsWith('file://', $service->localImageUri($source));
                $this->assertStringStartsWith('data:image/png;base64,', $service->browserImageUri($source));
            }
            $this->assertNull($service->localImageUri('https://untrusted.test/uploads/photo.png'));
            $this->assertNull($service->localImageUri('uploads/../../.env'));
            $this->assertNull($service->browserImageUri('file://' . $directory . '/photo.png'));
            $this->assertStringContainsString('src="data:image/png;base64,', $service->browserImages('<img src="../uploads/photo.png">'));
        } finally {
            unlink($directory . '/photo.png');
            rmdir($directory);
        }
    }
}
