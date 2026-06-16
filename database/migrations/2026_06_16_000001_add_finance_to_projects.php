<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('contract_value', 16, 2)->default(0)->after('budget');
            $table->decimal('down_payment', 16, 2)->default(0)->after('contract_value');
        });

        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // material, upah, operasional
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 14, 2)->default(1);
            $table->decimal('unit_cost', 16, 2)->default(0);
            $table->decimal('amount', 16, 2)->default(0);
            $table->date('date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_costs');
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['contract_value', 'down_payment']);
        });
    }
};
