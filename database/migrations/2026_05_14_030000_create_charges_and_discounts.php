<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('service_fee_percentage', 5, 2)->default(0);
            $table->boolean('is_tax_active')->default(false);
            $table->boolean('is_service_fee_active')->default(false);
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->decimal('minimum_spend', 14, 2)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'code']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('discount_code')->nullable()->after('discount_id');
            $table->string('discount_type')->nullable()->after('discount_code');
            $table->decimal('discount_value', 14, 2)->default(0)->after('discount_type');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('discount_total');
            $table->decimal('service_fee_percentage', 5, 2)->default(0)->after('tax_percentage');
            $table->decimal('service_fee_total', 14, 2)->default(0)->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_id');
            $table->dropColumn([
                'discount_code',
                'discount_type',
                'discount_value',
                'tax_percentage',
                'service_fee_percentage',
                'service_fee_total',
            ]);
        });

        Schema::dropIfExists('discounts');
        Schema::dropIfExists('store_charges');
    }
};
