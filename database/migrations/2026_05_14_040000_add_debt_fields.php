<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('outstanding_debt', 14, 2)->default(0)->after('total_spent');
            $table->decimal('debt_total', 14, 2)->default(0)->after('outstanding_debt');
            $table->timestamp('last_debt_at')->nullable()->after('last_purchase_at');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_debt')->default(false)->after('payment_proof');
            $table->decimal('debt_amount', 14, 2)->default(0)->after('change_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_debt', 'debt_amount']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['outstanding_debt', 'debt_total', 'last_debt_at']);
        });
    }
};
