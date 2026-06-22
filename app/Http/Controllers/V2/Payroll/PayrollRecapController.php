<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Rekap bulanan: gaji (dari payroll) dan bon/kasbon (dari employee_loans),
 * dipisah menjadi dua laporan.
 */
class PayrollRecapController extends Controller
{
    // ---------- Rekap Gaji ----------

    public function salary(Request $request): View
    {
        return view('v2.payroll.recap.salary', $this->salaryData($request));
    }

    public function salaryPrint(Request $request): View
    {
        return view('v2.payroll.recap.salary-print', $this->salaryData($request));
    }

    // ---------- Rekap Bon / Kasbon ----------

    public function loan(Request $request): View
    {
        return view('v2.payroll.recap.loan', $this->loanData($request));
    }

    public function loanPrint(Request $request): View
    {
        return view('v2.payroll.recap.loan-print', $this->loanData($request));
    }

    /**
     * @return array{start:string,end:string,month:string,periodLabel:string}
     */
    private function period(Request $request): array
    {
        $month = $request->string('month')->value() ?: now()->format('Y-m');
        $p = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [
            'start' => $p->copy()->startOfMonth()->toDateString(),
            'end' => $p->copy()->endOfMonth()->toDateString(),
            'month' => $month,
            'periodLabel' => $p->translatedFormat('F Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salaryData(Request $request): array
    {
        $period = $this->period($request);
        $employeeId = $this->employeeId($request);

        $payrolls = Payroll::query()
            ->with('employee')
            ->where('company_id', $this->companyId())
            ->whereDate('period_start', '>=', $period['start'])
            ->whereDate('period_start', '<=', $period['end'])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->get()
            ->sortBy(fn (Payroll $p) => $p->employee?->name)
            ->values();

        $totals = [
            'total_hours' => (float) $payrolls->sum('total_hours'),
            'gross_salary' => (float) $payrolls->sum('gross_salary'),
            'total_bonus' => (float) $payrolls->sum('total_bonus'),
            'total_loan' => (float) $payrolls->sum('total_loan'),
            'total_deduction' => (float) $payrolls->sum('total_deduction'),
            'total_arisan' => (float) $payrolls->sum('total_arisan'),
            'total_savings' => (float) $payrolls->sum('total_savings'),
            'carry_over' => (float) $payrolls->sum('carry_over'),
            'take_home_pay' => (float) $payrolls->sum('take_home_pay'),
        ];

        return array_merge($period, [
            'payrolls' => $payrolls,
            'totals' => $totals,
            'company' => Company::query()->first(),
            'employees' => $this->employeeOptions(),
            'employeeId' => $employeeId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loanData(Request $request): array
    {
        $period = $this->period($request);
        $employeeId = $this->employeeId($request);

        $loans = EmployeeLoan::query()
            ->with('employee')
            ->whereHas('employee', fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('date', '>=', $period['start'])
            ->whereDate('date', '<=', $period['end'])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->get()
            ->sortBy([fn (EmployeeLoan $l) => $l->employee?->name, fn (EmployeeLoan $l) => $l->date])
            ->values();

        $totals = [
            'amount' => (float) $loans->sum('amount'),
            'repaid' => (float) $loans->sum(fn (EmployeeLoan $l) => (float) $l->amount - (float) $l->outstanding),
            'outstanding' => (float) $loans->sum('outstanding'),
        ];

        return array_merge($period, [
            'loans' => $loans,
            'totals' => $totals,
            'company' => Company::query()->first(),
            'employees' => $this->employeeOptions(),
            'employeeId' => $employeeId,
        ]);
    }

    private function employeeId(Request $request): ?int
    {
        $id = $request->integer('employee_id');

        return $id > 0 ? $id : null;
    }

    /**
     * @return Collection<int, Employee>
     */
    private function employeeOptions(): Collection
    {
        return Employee::query()
            ->where('company_id', $this->companyId())
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
