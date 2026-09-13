<?php

namespace Tests\Unit;

use App\Services\ContractService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    #[Test]
    public function it_sanitizes_csv_formula_values(): void
    {
        $service = new ContractService();

        $this->assertSame("'=SUM(A1:A2)", $service->csvSafeValue('=SUM(A1:A2)'));
        $this->assertSame("'+1", $service->csvSafeValue('+1'));
        $this->assertSame("'-1", $service->csvSafeValue('-1'));
        $this->assertSame("'@foo", $service->csvSafeValue('@foo'));
        $this->assertSame('normal', $service->csvSafeValue('normal'));
    }

    #[Test]
    public function it_strips_scripts_and_event_handlers_from_html(): void
    {
        $service = new ContractService();

        $html = '<div onclick="alert(1)"><script>alert(2)</script><style>body{}</style><a href="javascript:alert(3)">link</a><form><img src="x" onerror="alert(4)"></form></div>';
        $sanitized = $service->sanitizeHtml($html);

        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('<style', $sanitized);
        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringNotContainsString('onerror=', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
    }
}
