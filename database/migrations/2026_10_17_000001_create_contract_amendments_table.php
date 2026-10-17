<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_amendments')) {
            return;
        }

        // Audit trail for edits made to contracts after they left draft.
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contract_id')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('contract_status', 50)->nullable();
            $table->string('workflow_status', 50)->nullable();
            $table->text('reason');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_amendments');
    }
};
