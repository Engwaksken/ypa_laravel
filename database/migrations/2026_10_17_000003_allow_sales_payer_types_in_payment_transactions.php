<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions') || !Schema::hasColumn('payment_transactions', 'payer_type')) {
            return;
        }

        $column = collect(Schema::getColumns('payment_transactions'))->firstWhere('name', 'payer_type');

        // Legacy enums only cover membership payments. Sales also use customer and walkin.
        // Retain existing values, nullability and defaults while allowing sales payer types.
        Schema::table('payment_transactions', function (Blueprint $table) use ($column) {
            $payerType = $table->string('payer_type', 50)->nullable($column['nullable']);
            if ($column['default'] !== null) {
                // SQLite exposes the SQL expression; MySQL exposes the literal value.
                $payerType->default(Schema::getConnection()->getDriverName() === 'sqlite'
                    ? DB::raw($column['default'])
                    : $column['default']);
            }
            if (!empty($column['comment'])) {
                $payerType->comment($column['comment']);
            }
            $payerType->change();
        });
    }

    public function down(): void
    {
        // Do not restore the legacy enum: existing sales payer types would be truncated.
    }
};
