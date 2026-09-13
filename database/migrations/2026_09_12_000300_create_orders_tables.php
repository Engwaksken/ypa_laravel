<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_number', 50)->unique();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name', 191);
                $table->string('phone', 50);
                $table->string('email', 150)->nullable();
                $table->string('delivery_location', 500);
                $table->unsignedBigInteger('branch_id')->index();
                $table->string('payment_method', 50);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->unsignedInteger('orders_count')->default(1);
                $table->string('status', 20)->default('pending')->index();
                $table->text('notes')->nullable();
                $table->boolean('is_guest_order')->default(false);
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('product_name', 191);
                $table->string('sku', 100)->nullable();
                $table->unsignedInteger('quantity');
                $table->decimal('price', 15, 2);
                $table->decimal('subtotal', 15, 2);
            });
        }
    }

    public function down(): void
    {
        // Orders are business records and may already exist in production.
        // They must be removed only through an explicit data migration.
    }
};
