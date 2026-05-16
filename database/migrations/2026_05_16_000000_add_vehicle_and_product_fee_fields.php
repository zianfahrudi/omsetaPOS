<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('goods')->after('image_url');
            $table->decimal('fee_amount', 14, 2)->default(0)->after('sell_price');
            $table->decimal('product_service_fee', 14, 2)->default(0)->after('fee_amount');
        });

        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number');
            $table->unsignedInteger('mileage')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'plate_number']);
            $table->index(['store_id', 'plate_number']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_vehicle_id')->nullable()->after('customer_id')->constrained('customer_vehicles')->nullOnDelete();
            $table->string('vehicle_plate_number')->nullable()->after('customer_phone');
            $table->unsignedInteger('vehicle_mileage')->nullable()->after('vehicle_plate_number');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('product_type')->default('goods')->after('product_code');
            $table->decimal('fee_amount', 14, 2)->default(0)->after('unit_price');
            $table->decimal('service_fee_amount', 14, 2)->default(0)->after('fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'fee_amount', 'service_fee_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_vehicle_id');
            $table->dropColumn(['vehicle_plate_number', 'vehicle_mileage']);
        });

        Schema::dropIfExists('customer_vehicles');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'fee_amount', 'product_service_fee']);
        });
    }
};
