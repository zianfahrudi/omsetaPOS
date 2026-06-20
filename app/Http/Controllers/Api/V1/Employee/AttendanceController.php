<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Employee\AttendanceCheckRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Services\AttendanceVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceVerifier $verifier) {}

    /**
     * Status presensi hari ini + titik lokasi yang berlaku.
     */
    public function today(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        return response()->json([
            'date' => $today,
            'can_check_in' => ! $attendance || ! $attendance->check_in,
            'can_check_out' => $attendance && $attendance->check_in && ! $attendance->check_out,
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
        ]);
    }

    public function checkIn(AttendanceCheckRequest $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        if ($attendance && $attendance->check_in) {
            throw ValidationException::withMessages(['check_in' => ['Anda sudah check-in hari ini.']]);
        }

        $result = $this->verifier->verify(
            $employee,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $request->filled('accuracy') ? (float) $request->input('accuracy') : null,
            (bool) $request->boolean('is_mock'),
            $request->string('device_id')->value() ?: null,
        );

        $schedule = $this->todaySchedule($employee, $today);
        $now = now();

        $attendance ??= new Attendance([
            'employee_id' => $employee->id,
            'work_date' => $today,
        ]);

        $attendance->fill([
            'shift_id' => $schedule?->shift_id,
            'check_in' => $now,
            'status' => $this->resolveStatus($schedule, $now),
            'source' => 'mobile',
            'hourly_rate' => $employee->hourly_rate,
            'check_in_location_id' => $result['location']->id,
            'check_in_latitude' => $request->input('latitude'),
            'check_in_longitude' => $request->input('longitude'),
            'check_in_accuracy' => $request->input('accuracy'),
            'check_in_distance' => $result['distance'],
            'check_in_is_mock' => (bool) $request->boolean('is_mock'),
            'device_id' => $request->string('device_id')->value() ?: $attendance->device_id,
        ]);
        $attendance->recalculate();
        $attendance->save();

        return response()->json([
            'message' => 'Check-in berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ], 201);
    }

    public function checkOut(AttendanceCheckRequest $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        if (! $attendance || ! $attendance->check_in) {
            throw ValidationException::withMessages(['check_out' => ['Anda belum check-in hari ini.']]);
        }

        if ($attendance->check_out) {
            throw ValidationException::withMessages(['check_out' => ['Anda sudah check-out hari ini.']]);
        }

        $result = $this->verifier->verify(
            $employee,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $request->filled('accuracy') ? (float) $request->input('accuracy') : null,
            (bool) $request->boolean('is_mock'),
            $request->string('device_id')->value() ?: null,
        );

        $attendance->fill([
            'check_out' => now(),
            'check_out_location_id' => $result['location']->id,
            'check_out_latitude' => $request->input('latitude'),
            'check_out_longitude' => $request->input('longitude'),
            'check_out_accuracy' => $request->input('accuracy'),
            'check_out_distance' => $result['distance'],
            'check_out_is_mock' => (bool) $request->boolean('is_mock'),
        ]);
        $attendance->recalculate();
        $attendance->save();

        return response()->json([
            'message' => 'Check-out berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    /**
     * Riwayat presensi karyawan (terbaru dulu).
     */
    public function history(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $items = Attendance::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 30), 100));

        return response()->json([
            'data' => collect($items->items())->map(fn (Attendance $a) => $this->attendancePayload($a)),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Jadwal shift mendatang karyawan.
     */
    public function schedule(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $schedules = EmployeeSchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', now()->toDateString())
            ->orderBy('work_date')
            ->limit(30)
            ->get();

        return response()->json([
            'data' => $schedules->map(fn (EmployeeSchedule $s) => [
                'work_date' => $s->work_date->toDateString(),
                'shift' => $s->shift ? [
                    'id' => $s->shift->id,
                    'name' => $s->shift->name,
                    'start_time' => $s->shift->start_time,
                    'end_time' => $s->shift->end_time,
                ] : null,
            ]),
        ]);
    }

    private function todaySchedule(Employee $employee, string $date): ?EmployeeSchedule
    {
        return EmployeeSchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $date)
            ->orderBy('shift_id')
            ->first();
    }

    private function resolveStatus(?EmployeeSchedule $schedule, Carbon $checkIn): string
    {
        $start = $schedule?->shift?->start_time;
        if (! $start) {
            return 'present';
        }

        $scheduledStart = Carbon::parse($checkIn->toDateString().' '.$start);

        return $checkIn->greaterThan($scheduledStart) ? 'late' : 'present';
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePayload(Attendance $a): array
    {
        return [
            'id' => $a->id,
            'work_date' => $a->work_date->toDateString(),
            'status' => $a->status,
            'source' => $a->source,
            'check_in' => $a->check_in?->toIso8601String(),
            'check_out' => $a->check_out?->toIso8601String(),
            'total_hours' => (float) $a->total_hours,
            'check_in_distance' => $a->check_in_distance !== null ? (float) $a->check_in_distance : null,
            'check_out_distance' => $a->check_out_distance !== null ? (float) $a->check_out_distance : null,
        ];
    }
}
