<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_templates') && ! Schema::hasColumn('contract_templates', 'project_id')) {
            Schema::table('contract_templates', function (Blueprint $table): void {
                $table->unsignedBigInteger('project_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contract_templates') && Schema::hasColumn('contract_templates', 'project_id')) {
            Schema::table('contract_templates', function (Blueprint $table): void {
                if (Schema::hasIndex('contract_templates', 'contract_templates_project_id_index')) {
                    $table->dropIndex('contract_templates_project_id_index');
                }
                $table->dropColumn('project_id');
            });
        }
    }
};
