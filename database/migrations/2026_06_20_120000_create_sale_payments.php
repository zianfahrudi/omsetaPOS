<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rincian pembayaran per transaksi POS: mendukung metode gabungan
        // (mis. cash + transfer) dan cicilan/pelunasan hutang.
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // cash|qris|transfer
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('proof')->nullable();
            $table->boolean('is_settlement')->default(false); // true = pelunasan/cicilan hutang setelah transaksi
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
