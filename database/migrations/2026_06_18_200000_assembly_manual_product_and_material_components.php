<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            // Produk jadi boleh dari master produk ATAU diisi manual (nama bebas).
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->string('product_name')->nullable()->after('product_id');
        });

        Schema::table('assembly_components', function (Blueprint $table) {
            // Komponen diambil dari master material (boleh juga produk untuk kompatibilitas).
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->after('product_id')->constrained('materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assembly_components', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
        });
        Schema::table('assemblies', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
};
