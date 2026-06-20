<?php

namespace App\Services;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Support\Geolocation;
use Illuminate\Validation\ValidationException;

class AttendanceVerifier
{
    /**
     * Hasil verifikasi presensi.
     *
     * @return array{location: AttendanceLocation, distance: float}
     *
     * @throws ValidationException bila gagal (di luar radius, mock GPS, akurasi buruk, dst.)
     */
    public function verify(Employee $employee, float $latitude, float $longitude, ?float $accuracy, bool $isMock, ?string $deviceId = null): array
    {
        // 1. Anti-fake-GPS: tolak mock/fake location.
        if ($isMock && config('attendance.reject_mock_location')) {
            $this->fail('Lokasi palsu (fake GPS) terdeteksi. Matikan aplikasi pemalsu lokasi lalu coba lagi.');
        }

        // 2. Akurasi GPS harus wajar. Akurasi sangat buruk sering tanda lokasi jaringan/palsu.
        $maxAccuracy = (int) config('attendance.max_accuracy_meters');
        if ($accuracy !== null && $maxAccuracy > 0 && $accuracy > $maxAccuracy) {
            $this->fail("Akurasi GPS terlalu rendah ({$accuracy} m). Pastikan GPS aktif di luar ruangan lalu coba lagi.");
        }

        // 3. Binding perangkat (opsional) untuk cegah akun dipakai di HP lain.
        if (config('attendance.bind_device') && $employee->device_id && $deviceId && $employee->device_id !== $deviceId) {
            $this->fail('Perangkat tidak dikenali. Hubungi admin untuk mendaftarkan ulang perangkat.');
        }

        // 4. Tentukan titik lokasi presensi yang berlaku.
        $location = $this->resolveLocation($employee);

        // 5. Cek geofence: jarak harus dalam radius + buffer.
        $distance = Geolocation::distanceMeters(
            (float) $location->latitude,
            (float) $location->longitude,
            $latitude,
            $longitude,
        );

        $allowed = (int) $location->radius_meters + (int) config('attendance.radius_buffer_meters');
        if ($distance > $allowed) {
            $this->fail("Anda berada {$distance} m dari titik presensi \"{$location->name}\" (maks {$location->radius_meters} m). Mendekatlah ke lokasi.");
        }

        return ['location' => $location, 'distance' => $distance];
    }

    /**
     * Titik presensi: yang ditugaskan ke karyawan, jika tidak pilih titik terdekat milik perusahaan.
     */
    private function resolveLocation(Employee $employee): AttendanceLocation
    {
        if ($employee->attendance_location_id) {
            $location = AttendanceLocation::query()
                ->whereKey($employee->attendance_location_id)
                ->where('is_active', true)
                ->first();

            if ($location) {
                return $location;
            }
        }

        // Fallback: titik aktif pertama milik perusahaan karyawan.
        $location = AttendanceLocation::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->first();

        if (! $location) {
            $this->fail('Belum ada titik lokasi presensi yang ditentukan. Hubungi admin.');
        }

        return $location;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['location' => [$message]]);
    }
}
