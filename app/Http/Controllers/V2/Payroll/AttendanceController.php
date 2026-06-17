<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        $attendances = Attendance::query()
            ->with(['employee', 'shift'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('work_date', $date)
            ->get()
            ->sortBy('employee.name');

        return view('v2.payroll.attendances.index', [
            'attendances' => $attendances,
            'employees' => Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::where('company_id', $companyId)->where('is_active', true)->orderBy('start_time')->get(),
            'statuses' => Attendance::STATUSES,
            'date' => $date,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'work_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i,H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i,H:i:s'],
            'status' => ['required', 'in:'.implode(',', Attendance::STATUSES)],
        ]);

        $attendance = new Attendance([
            'employee_id' => $data['employee_id'],
            'shift_id' => $data['shift_id'] ?? null,
            'work_date' => $data['work_date'],
            'status' => $data['status'],
            'check_in' => $this->combine($data['work_date'], $data['check_in'] ?? null),
            'check_out' => $this->combine($data['work_date'], $data['check_out'] ?? null),
            'hourly_rate' => Employee::whereKey($data['employee_id'])->value('hourly_rate'),
        ]);
        $attendance->recalculate();
        $attendance->save();

        return redirect()->route('v2.attendances.index', ['date' => $data['work_date']])->with('status', 'Absensi disimpan.');
    }

    /**
     * Buat baris absensi otomatis dari jadwal shift pada tanggal tertentu.
     */
    public function generateFromSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate(['work_date' => ['required', 'date']]);
        $date = $data['work_date'];
        $companyId = $this->companyId();

        $schedules = EmployeeSchedule::query()
            ->with(['shift', 'employee'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('work_date', $date)
            ->get();

        $created = 0;
        foreach ($schedules as $schedule) {
            $exists = Attendance::query()
                ->where('employee_id', $schedule->employee_id)
                ->where('shift_id', $schedule->shift_id)
                ->whereDate('work_date', $date)
                ->exists();

            if (! $exists) {
                // Prefill jam = durasi normal shift; admin tinggal koreksi penyimpangan.
                $duration = (float) ($schedule->shift?->duration_hours ?? 0);

                Attendance::create([
                    'employee_id' => $schedule->employee_id,
                    'shift_id' => $schedule->shift_id,
                    'work_date' => $date,
                    'status' => 'present',
                    'total_minutes' => (int) round($duration * 60),
                    'total_hours' => $duration,
                    'paid_hours' => $duration,
                    'hourly_rate' => $schedule->employee?->hourly_rate,
                ]);
                $created++;
            }
        }

        return redirect()->route('v2.attendances.index', ['date' => $date])
            ->with('status', $created > 0 ? "{$created} absensi dibuat dari jadwal." : 'Tidak ada jadwal baru untuk dibuatkan absensi.');
    }

    public function checkIn(Attendance $attendance): RedirectResponse
    {
        if (! $attendance->check_in) {
            $attendance->check_in = now();
            $attendance->recalculate();
            $attendance->save();
        }

        return back()->with('status', 'Check-in dicatat.');
    }

    public function checkOut(Attendance $attendance): RedirectResponse
    {
        if ($attendance->check_in && ! $attendance->check_out) {
            $attendance->check_out = now();
            $attendance->recalculate();
            $attendance->save();
        }

        return back()->with('status', 'Check-out dicatat.');
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'paid_hours' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', Attendance::STATUSES)],
            'check_in' => ['nullable', 'date_format:H:i,H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i,H:i:s'],
        ]);

        // Edit jam check-in/out langsung bila field dikirim, lalu hitung ulang.
        if ($request->has('check_in') || $request->has('check_out')) {
            $date = $attendance->work_date->toDateString();
            $attendance->check_in = $this->combine($date, $data['check_in'] ?? null);
            $attendance->check_out = $this->combine($date, $data['check_out'] ?? null);
            $attendance->recalculate();
        }

        $attendance->paid_hours = $data['paid_hours'];
        $attendance->status = $data['status'];
        $attendance->save();

        return back()->with('status', 'Absensi diperbarui.');
    }

    /**
     * Grid absensi mingguan: karyawan (per jam) × 7 hari, input jam dibayar sekaligus.
     */
    public function weekly(Request $request): View
    {
        $companyId = $this->companyId();
        $anchor = $request->date('week_start') ?? now();
        $start = $anchor->copy()->startOfDay();
        $dates = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('earning_type', 'hourly')
            ->orderBy('name')
            ->get();

        $attendances = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('work_date', '>=', $start->toDateString())
            ->whereDate('work_date', '<=', $start->copy()->addDays(6)->toDateString())
            ->get();

        // grid[employee_id][Y-m-d] = total jam dibayar hari itu.
        $grid = [];
        foreach ($attendances as $a) {
            $key = $a->work_date->toDateString();
            $grid[$a->employee_id][$key] = ($grid[$a->employee_id][$key] ?? 0) + $a->payableHours();
        }

        $standardHours = (float) Shift::query()
            ->where('company_id', $companyId)->where('is_active', true)->sum('duration_hours');
        if ($standardHours <= 0) {
            $standardHours = 7;
        }

        return view('v2.payroll.attendances.weekly', [
            'employees' => $employees,
            'dates' => $dates,
            'grid' => $grid,
            'weekStart' => $start->toDateString(),
            'standardHours' => $standardHours,
        ]);
    }

    /**
     * Simpan grid mingguan. Sel kosong dilewati; nilai 0 = tidak hadir.
     */
    public function weeklySave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'hours' => ['array'],
            'hours.*' => ['array'],
            'hours.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $companyId = $this->companyId();
        $employees = Employee::query()
            ->where('company_id', $companyId)->where('is_active', true)
            ->where('earning_type', 'hourly')->get()->keyBy('id');

        $count = 0;
        foreach (($data['hours'] ?? []) as $employeeId => $days) {
            $employee = $employees->get((int) $employeeId);
            if (! $employee) {
                continue;
            }

            foreach ($days as $date => $val) {
                if ($val === '' || $val === null) {
                    continue; // kosong: biarkan data lama apa adanya
                }
                $hours = (float) $val;

                $existing = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $date)
                    ->orderBy('id')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'paid_hours' => $hours,
                        'status' => $hours > 0 ? ($existing->status === 'absent' ? 'present' : $existing->status) : 'absent',
                    ]);
                } else {
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'work_date' => $date,
                        'status' => $hours > 0 ? 'present' : 'absent',
                        'total_minutes' => (int) round($hours * 60),
                        'total_hours' => $hours,
                        'paid_hours' => $hours,
                        'hourly_rate' => $employee->hourly_rate,
                    ]);
                }
                $count++;
            }
        }

        return redirect()->route('v2.attendances.weekly', ['week_start' => $data['week_start']])
            ->with('status', "{$count} sel absensi disimpan.");
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $date = $attendance->work_date->toDateString();
        $attendance->delete();

        return redirect()->route('v2.attendances.index', ['date' => $date])->with('status', 'Absensi dihapus.');
    }

    private function combine(string $date, ?string $time): ?Carbon
    {
        return $time ? Carbon::parse($date.' '.$time) : null;
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
