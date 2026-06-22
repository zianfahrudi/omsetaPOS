<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun kas/bank spesifik yang dipilih saat pembayaran (mis. BCA, BNI, Kas Besar).
     * Nullable: data lama & pembayaran tanpa pilihan tetap valid (fallback ke akun
     * sistem default sesuai metode).
     */
    public function up(): void
    {
        foreach (['sales_invoice_payments', 'purchase_payments', 'sale_payments'] as $table) {
            if (! Schema::hasColumn($table, 'account_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('account_id')->nullable()->after('id')
                        ->constrained('accounts')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sales_invoice_payments', 'purchase_payments', 'sale_payments'] as $table) {
            if (Schema::hasColumn($table, 'account_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('account_id');
                });
            }
        }
    }
};
