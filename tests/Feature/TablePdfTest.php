<?php

namespace Tests\Feature;

use App\Http\Controllers\TablePdfController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TablePdfTest extends TestCase
{
    public function test_pdf_generation_requires_authentication(): void
    {
        $this->post(route('documents.table-pdf'), ['title' => 'Members', 'headers' => ['Name'], 'rows' => []])->assertRedirect(route('login'));
    }

    public function test_selected_columns_render_as_an_actual_inline_pdf(): void
    {
        $response = (new TablePdfController)->store(Request::create('/documents/table-pdf', 'POST', [
            'title' => 'Members', 'headers' => ['Name'], 'rows' => [['Jane Farmer']],
        ]));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_table_text_is_escaped_and_never_interpreted_as_template_html(): void
    {
        $html = view('documents.table-pdf', ['title' => '<script>title</script>', 'headers' => ['Name'],
            'rows' => [['<img src="file:///etc/passwd">']]])->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src="file:///etc/passwd"', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function test_mismatched_column_counts_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new TablePdfController)->store(Request::create('/documents/table-pdf', 'POST', [
            'title' => 'Members', 'headers' => ['Name'], 'rows' => [['Jane', 'extra']],
        ]));
    }

    public function test_rendering_cannot_execute_php_javascript_or_fetch_remote_files(): void
    {
        $options = app('dompdf')->getOptions();
        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertFalse($options->getIsRemoteEnabled());
        $this->assertSame(array_values(array_filter([realpath(public_path()), realpath(storage_path('app/public'))])), $options->getChroot());
    }
}
