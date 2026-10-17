<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Older databases may lack the item label columns the contract form and templates use. */
    private const COLUMNS = ['item_name', 'item_type', 'unit_name'];

    public function up(): void
    {
        if (! Schema::hasTable('contract_items')) {
            return;
        }
        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('contract_items', $column)) {
                Schema::table('contract_items', fn (Blueprint $table) => $table->string($column, 255)->nullable());
            }
        }
    }

    public function down(): void
    {
        // Columns may hold data that predates this migration, so they are kept.
    }
};
