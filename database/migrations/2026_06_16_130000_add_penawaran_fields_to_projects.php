<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('location')->nullable()->after('name');
            $table->decimal('overhead_percent', 8, 2)->default(0)->after('contract_value');
            $table->decimal('profit_percent', 8, 2)->default(0)->after('overhead_percent');
        });

        Schema::table('project_costs', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('quantity');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('default_overhead_percent', 8, 2)->default(10)->after('currency');
            $table->decimal('default_profit_percent', 8, 2)->default(10)->after('default_overhead_percent');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['location', 'overhead_percent', 'profit_percent']);
        });

        Schema::table('project_costs', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['default_overhead_percent', 'default_profit_percent']);
        });
    }
};
