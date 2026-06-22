<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Penautan petugas per item (mekanik/salesman). Nullable agar
            // transaksi lama & item tanpa petugas tetap valid
            // (backward compatibility, D3/Req 6).
            $table->foreignId('employee_id')->nullable()->after('product_type')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
