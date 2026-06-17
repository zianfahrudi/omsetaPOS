<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Realisasi/biaya aktual proyek (dibanding RAB sebagai anggaran).
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('category')->default('material'); // material|upah|operasional|lainnya
            $table->string('description')->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'category']);
        });

        // Termin pembayaran proyek (DP, progress, pelunasan).
        Schema::create('project_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('name');                 // mis. "DP", "Progress 50%", "Pelunasan"
            $table->decimal('amount', 16, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payment_terms');
        Schema::dropIfExists('project_expenses');
    }
};
