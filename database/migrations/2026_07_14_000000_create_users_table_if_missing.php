<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->string('password', 255)->nullable();
            $table->string('role', 50)->default('customer')->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('profile_pic', 255)->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->dateTime('code_expires')->nullable();
        });
    }

    public function down(): void
    {
        // The users table is a legacy application table and may predate this
        // migration. Rollbacks must never remove authentication data.
    }
};
