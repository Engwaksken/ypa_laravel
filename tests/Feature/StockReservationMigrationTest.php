<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockReservationMigrationTest extends TestCase
{
    public function test_migration_is_repeatable_and_rollback_preserves_business_evidence(): void
    {
        $migration = require database_path('migrations/2026_10_03_000001_create_order_stock_reservations_table.php');
        $migration->up();
        DB::table('order_stock_reservations')->insert([
            'order_id' => 12, 'stock_id' => 34, 'quantity' => 5, 'released_at' => null,
        ]);
        $migration->up();
        $migration->down();

        $this->assertTrue(Schema::hasTable('order_stock_reservations'));
        $this->assertDatabaseCount('order_stock_reservations', 1);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => 12, 'stock_id' => 34, 'quantity' => 5, 'released_at' => null,
        ]);
    }

    public function test_database_rejects_duplicate_reservations_for_the_same_order_and_stock(): void
    {
        (require database_path('migrations/2026_10_03_000001_create_order_stock_reservations_table.php'))->up();
        $reservation = ['order_id' => 12, 'stock_id' => 34, 'quantity' => 5];
        DB::table('order_stock_reservations')->insert($reservation);

        $this->expectException(QueryException::class);
        DB::table('order_stock_reservations')->insert($reservation);
    }
}
