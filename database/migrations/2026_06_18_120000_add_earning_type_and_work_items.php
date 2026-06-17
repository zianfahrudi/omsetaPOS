<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // hourly = digaji per jam (absensi); piecework = borongan/proyek (item pekerjaan).
            $table->string('earning_type')->default('hourly')->after('hourly_rate');
        });

        // Item pekerjaan borongan/proyek (mis. LEMARI 10 × Rp30.000, GERBANG Rp500.000).
        Schema::create('employee_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_items');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('earning_type');
        });
    }
};
