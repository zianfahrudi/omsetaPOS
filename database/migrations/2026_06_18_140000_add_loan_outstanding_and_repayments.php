<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            // Sisa utang berjalan. Bon dilunasi lewat cicilan (repayment).
            $table->decimal('outstanding', 15, 2)->default(0)->after('amount');
        });

        // Backfill: bon "pending" dianggap masih utuh, lainnya (paid/deducted) lunas.
        DB::table('employee_loans')->where('status', 'pending')->update(['outstanding' => DB::raw('amount')]);
        DB::table('employee_loans')->where('status', '!=', 'pending')->update(['outstanding' => 0]);

        // Buku cicilan bon: tiap potongan/angsuran (kolom BON di Excel).
        Schema::create('employee_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_repayments');
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropColumn('outstanding');
        });
    }
};
