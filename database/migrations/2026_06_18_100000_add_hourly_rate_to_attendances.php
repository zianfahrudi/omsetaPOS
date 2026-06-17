<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Snapshot tarif/jam karyawan saat absensi dibuat, agar payroll lama
            // tetap memakai tarif yang berlaku waktu itu meski tarif karyawan berubah.
            $table->decimal('hourly_rate', 15, 2)->nullable()->after('paid_hours');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
