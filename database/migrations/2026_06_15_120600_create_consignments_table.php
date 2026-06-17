<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('date');
            $table->string('status')->default('open'); // open, closed
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->decimal('total_sold', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity');            // dikirim
            $table->integer('sold_quantity')->default(0);
            $table->integer('returned_quantity')->default(0);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('unit_cost', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
        Schema::dropIfExists('consignments');
    }
};
