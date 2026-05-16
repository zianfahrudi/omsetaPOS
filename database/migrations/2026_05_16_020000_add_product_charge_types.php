<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_service_fee_type')->default('fixed')->after('product_service_fee');
            $table->string('product_tax_type')->default('fixed')->after('product_service_fee_type');
            $table->decimal('product_tax_value', 14, 2)->default(0)->after('product_tax_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_service_fee_type',
                'product_tax_type',
                'product_tax_value',
            ]);
        });
    }
};
