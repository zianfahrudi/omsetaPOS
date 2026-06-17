<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use Illuminate\View\View;

class PayrollDashboardController extends Controller
{
    public function index(): View
    {
        $companyId = Company::query()->value('id');
        $employeeIds = Employee::where('company_id', $companyId)->pluck('id');
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $totalEmployees = Employee::where('company_id', $companyId)->where('is_active', true)->count();

        $totalHours = (float) Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $start)
            ->whereDate('work_date', '<=', $end)
            ->get()
            ->sum(fn (Attendance $a) => $a->payableHours());

        $totalPayroll = (float) Payroll::query()
            ->where('company_id', $companyId)
            ->whereDate('period_start', '>=', $start)
            ->whereDate('period_start', '<=', $end)
            ->sum('take_home_pay');

        $totalBonus = (float) EmployeeBonus::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        $totalLoan = (float) EmployeeLoan::query()
            ->whereIn('employee_id', $employeeIds)
            ->sum('outstanding');

        $recentPayrolls = Payroll::with('employee')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('v2.payroll.dashboard', compact(
            'totalEmployees', 'totalHours', 'totalPayroll', 'totalBonus', 'totalLoan', 'recentPayrolls', 'start', 'end'
        ));
    }
}
