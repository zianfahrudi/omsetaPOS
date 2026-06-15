<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            // asset | liability | equity | revenue | expense
            $table->string('type');
            // optional role hint: cash, bank, accounts_receivable, accounts_payable,
            // inventory, cogs, sales, sales_return, tax_output, tax_input, retained_earnings ...
            $table->string('subtype')->nullable();
            // debit | credit (normal balance side)
            $table->string('normal_balance');
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'subtype']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
