<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 1 business tables (notifications). Reproduces tables that already
 * exist in the production database. The migration is recorded as applied
 * in the `migrations` table, so `php artisan migrate` will skip creation
 * against production. It exists so fresh installs and the sqlite :memory:
 * test database can build the same shape. No foreign keys are added
 * (production data may not satisfy them); only indexes are reproduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('type', 50)->default('other')->index();
                $table->boolean('is_read')->default(false)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('setting_key')->unique();
                $table->text('setting_value')->nullable();
            });
        }
    }

    public function down(): void
    {
        // These tables may predate this migration in production. Never drop
        // a legacy table as part of a rollback.
    }
};
