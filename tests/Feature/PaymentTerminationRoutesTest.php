<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTerminationRoutesTest extends TestCase
{
    #[Test]
    public function it_registers_payment_and_termination_routes(): void
    {
        $this->assertTrue(Route::has('harvest-due.index'));
        $this->assertTrue(Route::has('harvest-due.record'));
        $this->assertTrue(Route::has('harvests.index'));
        $this->assertTrue(Route::has('harvests.create'));
        $this->assertTrue(Route::has('harvests.store'));
        $this->assertTrue(Route::has('harvests.show'));
        $this->assertTrue(Route::has('harvests.destroy'));
        $this->assertTrue(Route::has('harvests.export'));
        $this->assertTrue(Route::has('harvests.review'));
        $this->assertTrue(Route::has('harvests.approve'));
        $this->assertTrue(Route::has('harvests.reject'));
        $this->assertTrue(Route::has('harvests.pay'));

        $this->assertTrue(Route::has('payments.index'));
        $this->assertTrue(Route::has('payments.create'));
        $this->assertTrue(Route::has('payments.store'));
        $this->assertTrue(Route::has('payments.show'));
        $this->assertTrue(Route::has('payments.export'));
        $this->assertTrue(Route::has('payments.approve'));
        $this->assertTrue(Route::has('payments.reject'));
        $this->assertTrue(Route::has('payments.reconcile'));
        $this->assertTrue(Route::has('payments.receipt'));

        $this->assertTrue(Route::has('receivables.index'));
        $this->assertTrue(Route::has('receivables.create'));
        $this->assertTrue(Route::has('receivables.store'));
        $this->assertTrue(Route::has('receivables.show'));
        $this->assertTrue(Route::has('receivables.edit'));
        $this->assertTrue(Route::has('receivables.update'));
        $this->assertTrue(Route::has('receivables.destroy'));
        $this->assertTrue(Route::has('receivables.export'));
        $this->assertTrue(Route::has('receivables.pay'));

        $this->assertTrue(Route::has('termination.index'));
        $this->assertTrue(Route::has('termination.create'));
        $this->assertTrue(Route::has('termination.store'));
        $this->assertTrue(Route::has('termination.show'));
    }
}
