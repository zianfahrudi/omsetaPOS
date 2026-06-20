<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Employee\EmployeeLoginRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(EmployeeLoginRequest $request): JsonResponse
    {
        $phone = (string) $request->string('phone');
        $throttleKey = 'emp|'.Str::lower($phone).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'phone' => ["Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."],
            ])->status(429);
        }

        $employee = Employee::query()->where('phone', $phone)->first();

        if (! $employee || ! $employee->password || ! Hash::check((string) $request->input('password'), $employee->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'phone' => ['Nomor HP atau password salah.'],
            ]);
        }

        if (! $employee->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['Akun karyawan tidak aktif.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Daftarkan device_id pertama kali (binding perangkat opsional).
        if ($deviceId = $request->string('device_id')->value()) {
            if (! $employee->device_id) {
                $employee->forceFill(['device_id' => $deviceId])->save();
            }
        }

        $deviceName = $request->string('device_name')->value() ?: 'mobile';
        $token = $employee->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'employee' => $this->payload($employee),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['employee' => $this->payload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Employee $employee): array
    {
        $employee->loadMissing('attendanceLocation');
        $loc = $employee->attendanceLocation;

        return [
            'id' => $employee->id,
            'code' => $employee->code,
            'name' => $employee->name,
            'phone' => $employee->phone,
            'position' => $employee->position,
            'location' => $loc ? [
                'id' => $loc->id,
                'name' => $loc->name,
                'address' => $loc->address,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'radius_meters' => (int) $loc->radius_meters,
            ] : null,
        ];
    }
}
