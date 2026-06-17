<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan payroll per periode.
 *
 * Formula (PRD Modul 11):
 *   Take Home Pay = Gaji Kotor + Bonus − Kasbon − Arisan − Tabungan
 *
 * Prinsip "seluruh transaksi DALAM periode": bonus & kasbon yang dihitung
 * adalah yang tanggalnya berada di rentang periode (bukan seluruh histori).
 * Kasbon yang belum lunas (pending) di periode itu yang dipotong.
 */
class PayrollService
{
    /**
     * Hitung komponen payroll satu karyawan untuk satu periode.
     *
     * @return array<string, float>
     */
    public function computeForEmployee(Employee $employee, string $start, string $end): array
    {
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

        // Kasbon belum lunas yang tanggalnya di dalam periode ini.
        $loan = (float) EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        $arisan = (float) $employee->arisan->where('active', true)->sum('amount');
        $savings = (float) $employee->savings->where('active', true)->sum('amount');

        $thp = round($gross + $bonus - $loan - $arisan - $savings, 2);

        return [
            'total_hours' => $hours,
            'gross_salary' => $gross,
            'total_bonus' => round($bonus, 2),
            'total_loan' => round($loan, 2),
            'total_arisan' => round($arisan, 2),
            'total_savings' => round($savings, 2),
            'take_home_pay' => $thp,
        ];
    }

    /**
     * Generate/refresh payroll draft seluruh karyawan aktif untuk satu periode.
     * Payroll berstatus "paid" tidak ditimpa.
     *
     * @return array{generated:int,skipped:int}
     */
    public function generateForPeriod(int $companyId, string $start, string $end): array
    {
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['arisan', 'savings'])
            ->get();

        $generated = 0;
        $skipped = 0;

        DB::transaction(function () use ($employees, $companyId, $start, $end, &$generated, &$skipped) {
            foreach ($employees as $employee) {
                $existing = Payroll::query()
                    ->where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->whereDate('period_start', $start)
                    ->whereDate('period_end', $end)
                    ->first();

                if ($existing && $existing->status === 'paid') {
                    $skipped++;

                    continue;
                }

                $components = $this->computeForEmployee($employee, $start, $end);

                if ($existing) {
                    $existing->update(array_merge($components, ['status' => 'draft']));
                } else {
                    Payroll::create(array_merge($components, [
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'period_start' => $start,
                        'period_end' => $end,
                        'status' => 'draft',
                    ]));
                }
                $generated++;
            }
        });

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    /**
     * Tandai payroll dibayar + kasbon dalam periode itu sebagai "deducted".
     */
    public function markPaid(Payroll $payroll): void
    {
        if ($payroll->status === 'paid') {
            return;
        }

        DB::transaction(function () use ($payroll) {
            $payroll->update(['status' => 'paid']);

            EmployeeLoan::query()
                ->where('employee_id', $payroll->employee_id)
                ->where('status', 'pending')
                ->whereDate('date', '>=', $payroll->period_start)
                ->whereDate('date', '<=', $payroll->period_end)
                ->update(['status' => 'deducted']);
        });
    }
}
