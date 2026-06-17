<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kartu/ledger pergerakan stok material (masuk dari beli, keluar dari rakit, dll).
        Schema::create('material_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type'); // purchase_in | assembly_out | adjustment
            $table->decimal('quantity', 14, 2);       // bertanda: + masuk, - keluar
            $table->decimal('stock_before', 14, 2)->default(0);
            $table->decimal('stock_after', 14, 2)->default(0);
            $table->decimal('unit_cost', 16, 2)->default(0);
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['material_id', 'date']);
        });

        // Belanja bahan (pembelian material).
        Schema::create('material_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete(); // supplier
            $table->string('number');
            $table->date('date');
            $table->string('payment_method')->default('cash'); // cash | bank | credit
            $table->decimal('total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('material_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('unit_cost', 16, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_purchase_items');
        Schema::dropIfExists('material_purchases');
        Schema::dropIfExists('material_movements');
    }
};
