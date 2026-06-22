<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun pendapatan per kategori produk. Bila diisi, penjualan produk kategori
     * tsb dikreditkan ke akun ini → Laba Rugi memecah "Penjualan" per kategori.
     * Nullable: tanpa pemetaan, penjualan tetap ke akun "Penjualan" default.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('revenue_account_id')->nullable()->after('parent_id')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revenue_account_id');
        });
    }
};
