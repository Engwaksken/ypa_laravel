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
        $this->assertTrue(Route::has('branches.index'));
        $this->assertTrue(Route::has('products.index'));
        $this->assertTrue(Route::has('categories.store'));
        $this->assertTrue(Route::has('stock.index'));
        $this->assertTrue(Route::has('customers.index'));
        $this->assertTrue(Route::has('suppliers.index'));
        $this->assertTrue(Route::has('orders.index'));
        $this->assertTrue(Route::has('orders.status'));
        $this->assertTrue(Route::has('place-order.store'));
    }
}
