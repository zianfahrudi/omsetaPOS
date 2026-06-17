<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_costs', function (Blueprint $table) {
            // Urutan baris RAB (sesuai input, seperti No 1..N di Excel).
            $table->integer('sort_order')->default(0)->after('id');
        });

        Schema::table('projects', function (Blueprint $table) {
            // PPN opsional atas penawaran (mis. 11%).
            $table->decimal('tax_percent', 8, 2)->default(0)->after('profit_percent');
        });
    }

    public function down(): void
    {
        Schema::table('project_costs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('tax_percent');
        });
    }
};
