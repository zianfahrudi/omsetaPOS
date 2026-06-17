<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Potongan ad-hoc per karyawan (POTONGAN di Excel), mengikuti pola employee_bonus.
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            // POTONGAN umum (selain kasbon/tabungan/arisan).
            $table->decimal('total_deduction', 15, 2)->default(0)->after('total_loan');
            // SISA GAJI KEMARIN / penyesuaian manual (+/-) yang ditambahkan ke THP.
            $table->decimal('carry_over', 15, 2)->default(0)->after('total_savings');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['total_deduction', 'carry_over']);
        });
        Schema::dropIfExists('employee_deductions');
    }
};
