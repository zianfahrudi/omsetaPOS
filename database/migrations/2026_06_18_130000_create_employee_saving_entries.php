<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buku tabungan karyawan: setoran (deposit) & penarikan (withdraw),
        // sehingga saldo berjalan bisa dilacak (Excel: "5x setor", dll).
        Schema::create('employee_saving_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type')->default('deposit'); // deposit | withdraw
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_saving_entries');
    }
};
