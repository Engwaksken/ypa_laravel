<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'calculation_schema')) {
            Schema::table('projects', fn (Blueprint $table) => $table->json('calculation_schema')->nullable());
        }
        if (Schema::hasTable('contracts') && ! Schema::hasColumn('contracts', 'project_calculation')) {
            Schema::table('contracts', fn (Blueprint $table) => $table->json('project_calculation')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'project_calculation')) {
            Schema::table('contracts', fn (Blueprint $table) => $table->dropColumn('project_calculation'));
        }
        if (Schema::hasColumn('projects', 'calculation_schema')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('calculation_schema'));
        }
    }
};
