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
                Attendance::create([
                    'employee_id' => $schedule->employee_id,
                    'shift_id' => $schedule->shift_id,
                    'work_date' => $date,
                    'status' => 'present',
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
        ]);

        $attendance->update([
            'paid_hours' => $data['paid_hours'],
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Jam dibayar diperbarui.');
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
