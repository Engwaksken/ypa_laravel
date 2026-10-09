<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedInteger('user_id')->index();
                $table->unsignedBigInteger('branch_id')->index();
                $table->string('invoice_no', 50)->unique();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('balance_amount', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_status', 30);
                $table->decimal('partial_amount', 15, 2)->default(0);
                $table->string('customer_name')->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->date('sale_date')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sale_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedInteger('quantity');
                $table->decimal('price', 15, 2);
                $table->decimal('cost_price', 15, 2)->nullable();
                $table->decimal('total', 15, 2);
            });
        }
        if (!Schema::hasTable('pos_sale_requests')) {
            Schema::create('pos_sale_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('request_token')->unique();
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('sale_id')->unique();
                $table->string('payload_hash', 64);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Sales and deduplication evidence survive application rollbacks.
    }
};
