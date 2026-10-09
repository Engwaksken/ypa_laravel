<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_stock_reservations')) {
            Schema::create('order_stock_reservations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('stock_id')->index();
                $table->unsignedInteger('quantity');
                $table->timestamp('released_at')->nullable();
                $table->unique(['order_id', 'stock_id']);
            });
        }
    }

    public function down(): void
    {
        // Retain reservation evidence on rollback, as with legacy business tables.
    }
};
