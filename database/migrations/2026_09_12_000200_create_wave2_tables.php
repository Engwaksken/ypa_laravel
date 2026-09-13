<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 2 business tables (products, categories, stock, customers,
 * suppliers). Reproduces tables that already exist in the production
 * database. The migration is recorded as applied in the `migrations`
 * table, so `php artisan migrate` will skip creation against production.
 * It exists so fresh installs and the sqlite :memory: test database can
 * build the same shape. No foreign keys are added (production data may
 * not satisfy them); only indexes are reproduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('name');
                $table->string('sku', 100)->nullable()->unique();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->string('image_url')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('stock')) {
            Schema::create('stock', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('branch_id')->index();
                $table->integer('total_stock')->default(0);
                $table->integer('quantity')->default(0);
                $table->string('unit_type', 50)->nullable();
                $table->decimal('cost_price', 15, 2)->nullable();
                $table->date('expiry_date')->nullable()->index();
                $table->decimal('selling_price', 15, 2)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('customer_type', 50)->default('non_member')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('name');
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->text('address')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('supplier_type', 50)->default('non_member')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->text('address')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // These tables may predate this migration in production. Never drop
        // a legacy table as part of a rollback.
    }
};
