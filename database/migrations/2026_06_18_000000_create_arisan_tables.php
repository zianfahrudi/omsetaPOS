<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arisan_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('contribution_amount', 16, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_members')->default(0);
            $table->string('draw_method')->default('random'); // random, manual, queue
            $table->string('status')->default('draft');       // draft, active, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('arisan_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arisan_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('join_date')->nullable();
            $table->integer('sequence_number')->default(0);
            $table->boolean('has_won')->default(false);
            $table->string('status')->default('active'); // active, completed, withdrawn
            $table->timestamps();

            $table->unique(['arisan_group_id', 'employee_id']);
        });

        Schema::create('arisan_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arisan_group_id')->constrained()->cascadeOnDelete();
            $table->integer('period_no');
            $table->date('period_date');
            $table->decimal('total_collected', 16, 2)->default(0);
            $table->foreignId('winner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();

            $table->unique(['arisan_group_id', 'period_no']);
        });

        Schema::create('arisan_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arisan_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->date('contribution_date')->nullable();
            $table->string('status')->default('pending'); // paid, pending, cancelled
            $table->timestamps();
        });

        Schema::create('arisan_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arisan_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->date('payout_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arisan_payouts');
        Schema::dropIfExists('arisan_contributions');
        Schema::dropIfExists('arisan_periods');
        Schema::dropIfExists('arisan_members');
        Schema::dropIfExists('arisan_groups');
    }
};
