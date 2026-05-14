<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sales') || Schema::hasColumn('sales', 'payment_proof')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sales') || ! Schema::hasColumn('sales', 'payment_proof')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });
    }
};
