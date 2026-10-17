<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentPayerTypeMigrationTest extends TestCase
{
    public function test_migration_preserves_legacy_values_and_accepts_sales_payers(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('payer_type', ['member', 'group'])->nullable()->default('member');
        });
        DB::table('payment_transactions')->insert([
            ['payer_type' => 'member'], ['payer_type' => 'group'], ['payer_type' => null],
        ]);

        $migration = require database_path('migrations/2026_10_17_000003_allow_sales_payer_types_in_payment_transactions.php');
        $migration->up();
        $migration->up();

        DB::table('payment_transactions')->insert([
            ['payer_type' => 'walkin'], ['payer_type' => 'customer'],
        ]);
        DB::table('payment_transactions')->insert(['id' => 6]);
        $migration->down();

        $this->assertSame(['member', 'group', null, 'walkin', 'customer', 'member'],
            DB::table('payment_transactions')->orderBy('id')->pluck('payer_type')->all());
        $column = collect(Schema::getColumns('payment_transactions'))->firstWhere('name', 'payer_type');
        $this->assertTrue($column['nullable']);
    }

    public function test_migration_skips_missing_legacy_schema(): void
    {
        $migration = require database_path('migrations/2026_10_17_000003_allow_sales_payer_types_in_payment_transactions.php');
        $migration->up();
        $this->assertFalse(Schema::hasTable('payment_transactions'));

        Schema::create('payment_transactions', fn (Blueprint $table) => $table->increments('id'));
        $migration->up();
        $this->assertFalse(Schema::hasColumn('payment_transactions', 'payer_type'));
    }
}
