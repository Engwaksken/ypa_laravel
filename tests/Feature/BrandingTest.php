<?php

namespace Tests\Feature;

use App\Services\BrandingService;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    public function test_branding_falls_back_to_bundled_logo_and_name(): void
    {
        $brand = app(BrandingService::class);

        $this->assertSame(BrandingService::DEFAULT_LOGO, $brand->logoPath());
        $this->assertSame('Youth Platform Africa', $brand->name());
        $this->assertStringStartsWith('file://', (string) $brand->pdfLogo());
        $this->assertFileExists((string) $brand->emailLogoFile());
    }

    public function test_emails_render_with_logo_and_escaped_content(): void
    {
        $html = view('emails.verification-code', ['name' => '<b>Jane</b>', 'code' => '123456'])->render();

        $this->assertStringContainsString(asset(BrandingService::DEFAULT_LOGO), $html);
        $this->assertStringContainsString('Youth Platform Africa', $html);
        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('&lt;b&gt;Jane&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Jane</b>', $html);

        $reset = view('emails.password-reset', ['name' => 'Jane', 'resetLink' => 'https://example.test/reset'])->render();
        $this->assertStringContainsString('href="https://example.test/reset"', $reset);
        $this->assertStringContainsString(asset(BrandingService::DEFAULT_LOGO), $reset);
    }

    public function test_table_pdf_has_logo_letterhead_watermark_and_page_numbers(): void
    {
        $html = view('documents.table-pdf', ['title' => 'Members', 'headers' => ['Name'], 'rows' => [['Jane']]])->render();

        $logo = app(BrandingService::class)->pdfLogo();
        $this->assertStringContainsString('class="watermark"><img src="' . $logo . '"', $html);
        $this->assertStringContainsString('class="pdf-header"', $html);
        $this->assertStringContainsString('page-number', $html);
    }
}
