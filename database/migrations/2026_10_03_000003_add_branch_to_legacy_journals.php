<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journal_entries') && !Schema::hasColumn('journal_entries', 'branch_id')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Preserve branch attribution; historical NULL values require reconciliation.
    }
};
