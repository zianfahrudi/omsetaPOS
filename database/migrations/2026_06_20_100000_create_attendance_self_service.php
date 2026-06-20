<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Titik lokasi presensi (geofence) yang ditentukan admin.
        Schema::create('attendance_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Kredensial mobile + titik presensi yang ditugaskan ke karyawan.
        Schema::table('employees', function (Blueprint $table) {
            $table->string('password')->nullable()->after('phone');
            $table->foreignId('attendance_location_id')->nullable()->after('position')
                ->constrained('attendance_locations')->nullOnDelete();
            $table->string('device_id')->nullable()->after('attendance_location_id');
            $table->index('phone');
        });

        // Data geolokasi + anti-fake-GPS pada baris absensi.
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('status'); // manual|mobile
            $table->foreignId('check_in_location_id')->nullable()->after('source')
                ->constrained('attendance_locations')->nullOnDelete();
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_location_id');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->decimal('check_in_accuracy', 8, 2)->nullable()->after('check_in_longitude');
            $table->decimal('check_in_distance', 10, 2)->nullable()->after('check_in_accuracy');
            $table->boolean('check_in_is_mock')->default(false)->after('check_in_distance');

            $table->foreignId('check_out_location_id')->nullable()->after('check_in_is_mock')
                ->constrained('attendance_locations')->nullOnDelete();
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_location_id');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->decimal('check_out_accuracy', 8, 2)->nullable()->after('check_out_longitude');
            $table->decimal('check_out_distance', 10, 2)->nullable()->after('check_out_accuracy');
            $table->boolean('check_out_is_mock')->default(false)->after('check_out_distance');

            $table->string('device_id')->nullable()->after('check_out_is_mock');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('check_in_location_id');
            $table->dropConstrainedForeignId('check_out_location_id');
            $table->dropColumn([
                'source',
                'check_in_latitude', 'check_in_longitude', 'check_in_accuracy', 'check_in_distance', 'check_in_is_mock',
                'check_out_latitude', 'check_out_longitude', 'check_out_accuracy', 'check_out_distance', 'check_out_is_mock',
                'device_id',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_location_id');
            $table->dropIndex(['phone']);
            $table->dropColumn(['password', 'device_id']);
        });

        Schema::dropIfExists('attendance_locations');
    }
};
