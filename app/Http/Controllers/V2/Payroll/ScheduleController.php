<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();
        $employeeId = (int) $request->query('employee_id', 0);
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        $schedules = EmployeeSchedule::query()
            ->with(['employee', 'shift'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->whereDate('work_date', $date)
            ->get()
            ->sortBy([['employee.name', false]]);

        return view('v2.payroll.schedules.index', [
            'schedules' => $schedules,
            'employees' => Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::where('company_id', $companyId)->where('is_active', true)->orderBy('start_time')->get(),
            'employeeId' => $employeeId,
            'date' => $date,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_ids' => ['required', 'array', 'min:1'],
            'shift_ids.*' => ['integer', 'exists:shifts,id'],
            'work_date' => ['required', 'date'],
        ]);

        foreach ($data['shift_ids'] as $shiftId) {
            $exists = EmployeeSchedule::query()
                ->where('employee_id', $data['employee_id'])
                ->where('shift_id', $shiftId)
                ->whereDate('work_date', $data['work_date'])
                ->exists();

            if (! $exists) {
                EmployeeSchedule::create([
                    'employee_id' => $data['employee_id'],
                    'shift_id' => $shiftId,
                    'work_date' => $data['work_date'],
                ]);
            }
        }

        return redirect()
            ->route('v2.schedules.index', ['date' => $data['work_date'], 'employee_id' => $data['employee_id']])
            ->with('status', 'Jadwal shift disimpan.');
    }

    public function destroy(EmployeeSchedule $schedule): RedirectResponse
    {
        $date = $schedule->work_date->toDateString();
        $schedule->delete();

        return redirect()->route('v2.schedules.index', ['date' => $date])->with('status', 'Jadwal dihapus.');
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
