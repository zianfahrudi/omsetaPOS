<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->date('date');
            // in | out | transfer
            $table->string('type');
            // primary cash/bank account (source)
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            // counter account (income/expense) for in/out
            $table->foreignId('counter_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            // destination cash/bank account for transfer
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
