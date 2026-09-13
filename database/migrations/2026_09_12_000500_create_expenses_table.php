<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expenses module table (manual operational expenses). Reproduces the table
 * that already exists in the production database. The migration is recorded
 * as applied in the `migrations` table, so `php artisan migrate` will skip
 * creation against production. It exists so fresh installs and the sqlite
 * :memory: test database can build the same shape. No foreign keys are added
 * (production data may not satisfy them); only indexes are reproduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('title');
                $table->decimal('amount', 15, 2)->default(0);
                $table->date('expense_date')->index();
                $table->string('category', 100)->nullable()->index();
                $table->string('payment_method', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('debit_account_code', 20)->nullable();
                $table->string('credit_account_code', 20)->nullable();
                $table->unsignedBigInteger('journal_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // The table may predate this migration in production. Never drop a
        // legacy table as part of a rollback.
    }
};