<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects module tables (project_categories, projects). Reproduces tables
 * that already exist in the production database. The migration is recorded
 * as applied in the `migrations` table, so `php artisan migrate` will skip
 * creation against production. It exists so fresh installs and the sqlite
 * :memory: test database can build the same shape. No foreign keys are
 * added (production data may not satisfy them); only indexes are
 * reproduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_categories')) {
            Schema::create('project_categories', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('category_name');
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_category_id')->nullable()->index();
                $table->unsignedBigInteger('project_type_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('project_name');
                $table->string('project_code', 100)->unique();
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('registration_fee', 15, 2)->default(0)->index();
                $table->decimal('administrative_fee', 15, 2)->default(0);
                $table->string('status', 30)->default('Planning')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // These tables may predate this migration in production. Never drop
        // a legacy table as part of a rollback.
    }
};