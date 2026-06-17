<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $payrolls = Payroll::query()
            ->with('employee')
            ->where('company_id', $this->companyId())
            ->orderByDesc('period_start')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.payroll.payrolls.index', [
            'payrolls' => $payrolls,
            'defaultStart' => now()->startOfMonth()->toDateString(),
            'defaultEnd' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $companyId = $this->companyId();
        $start = $data['period_start'];
        $end = $data['period_end'];

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['arisan', 'savings'])
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $existing = Payroll::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->where('period_start', $start)
                ->where('period_end', $end)
                ->first();

            // Jangan timpa payroll yang sudah dibayar.
            if ($existing && $existing->status === 'paid') {
                continue;
            }

            $attendances = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', '>=', $start)
                ->whereDate('work_date', '<=', $end)
                ->get();

            $hours = round($attendances->sum(fn (Attendance $a) => $a->payableHours()), 2);
            $gross = round($hours * (float) $employee->hourly_rate, 2);

            $bonus = (float) EmployeeBonus::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end)
                ->sum('amount');

            $loan = (float) EmployeeLoan::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->whereDate('date', '<=', $end)
                ->sum('amount');

            $arisan = (float) $employee->arisan->where('active', true)->sum('amount');
            $savings = (float) $employee->savings->where('active', true)->sum('amount');

            $thp = round($gross + $bonus - $loan - $arisan - $savings, 2);

            Payroll::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'period_start' => $start,
                    'period_end' => $end,
                ],
                [
                    'total_hours' => $hours,
                    'gross_salary' => $gross,
                    'total_bonus' => $bonus,
                    'total_loan' => $loan,
                    'total_arisan' => $arisan,
                    'total_savings' => $savings,
                    'take_home_pay' => $thp,
                    'status' => 'draft',
                ],
            );
            $count++;
        }

        return redirect()->route('v2.payrolls.index')->with('status', "Payroll digenerate untuk {$count} karyawan periode {$start} s/d {$end}.");
    }

    public function show(Payroll $payroll): View
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $payroll->load('employee');

        return view('v2.payroll.payrolls.show', ['payroll' => $payroll]);
    }

    public function approve(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        if ($payroll->status === 'draft') {
            $payroll->update(['status' => 'approved']);
        }

        return back()->with('status', 'Payroll disetujui.');
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        if ($payroll->status !== 'paid') {
            $payroll->update(['status' => 'paid']);
            // Tandai kasbon yang dipotong sebagai 'deducted'.
            EmployeeLoan::query()
                ->where('employee_id', $payroll->employee_id)
                ->where('status', 'pending')
                ->whereDate('date', '<=', $payroll->period_end)
                ->update(['status' => 'deducted']);
        }

        return back()->with('status', 'Payroll ditandai sudah dibayar.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $payroll->delete();

        return redirect()->route('v2.payrolls.index')->with('status', 'Payroll dihapus.');
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
