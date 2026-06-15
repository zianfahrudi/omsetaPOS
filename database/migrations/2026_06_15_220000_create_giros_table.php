<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cleared_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('number');
            $table->string('giro_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 2);
            // received | deposited | cleared | rejected
            $table->string('status')->default('received');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giros');
    }
};
