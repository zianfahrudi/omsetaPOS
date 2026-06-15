<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->date('date');
            // general | sales | purchase | cash_receipt | cash_payment | inventory | opening | adjustment
            $table->string('type')->default('general');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            // draft | posted | void
            $table->string('status')->default('posted');
            // polymorphic source document (Sale, PurchaseInvoice, Payment, ...)
            $table->nullableMorphs('source');
            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
