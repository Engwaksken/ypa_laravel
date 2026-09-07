<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractRoutesTest extends TestCase
{
    #[Test]
    public function it_registers_the_contract_routes(): void
    {
        $this->assertTrue(Route::has('contracts.index'));
        $this->assertTrue(Route::has('contracts.export'));
        $this->assertTrue(Route::has('contracts.pdf'));
        $this->assertTrue(Route::has('contract-templates.index'));
        $this->assertTrue(Route::has('contract-templates.preview'));
    }
}
