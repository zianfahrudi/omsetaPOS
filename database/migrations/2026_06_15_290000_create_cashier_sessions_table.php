<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 18, 2)->default(0);
            $table->decimal('cash_sales_total', 18, 2)->default(0);
            $table->decimal('expected_cash', 18, 2)->default(0);
            $table->decimal('closing_cash', 18, 2)->default(0);
            $table->decimal('cash_difference', 18, 2)->default(0);
            // open | closed
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('number');
            $table->index(['store_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_sessions');
    }
};
