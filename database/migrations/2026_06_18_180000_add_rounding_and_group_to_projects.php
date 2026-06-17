<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Pembulatan total penawaran ke kelipatan (0 = tanpa pembulatan).
            $table->decimal('rounding_unit', 14, 2)->default(0)->after('tax_percent');
        });

        Schema::table('project_costs', function (Blueprint $table) {
            // Pengelompokan item RAB (mis. "Pekerjaan Aluminium", "Pekerjaan Kaca").
            $table->string('group_name')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('rounding_unit');
        });
        Schema::table('project_costs', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
