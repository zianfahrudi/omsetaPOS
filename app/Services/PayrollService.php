<?php

namespace App\Services;

use App\Models\ArisanContribution;
use App\Models\ArisanPeriod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeSavingEntry;
use App\Models\EmployeeWorkItem;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan payroll per periode.
 *
 * Formula:
 *   Take Home Pay = Gaji Kotor + Bonus + Sisa Gaji Kemarin
 *                   − Kasbon − Potongan − Tabungan
 *
 * Prinsip "seluruh transaksi DALAM periode": bonus, kasbon & potongan yang
 * dihitung adalah yang tanggalnya berada di rentang periode (bukan seluruh
 * histori). Kasbon yang belum lunas (pending) di periode itu yang dipotong.
 */
class PayrollService
{
    /**
     * Hitung komponen payroll satu karyawan untuk satu periode.
     *
     * @param  float  $carryOver  Sisa gaji kemarin / penyesuaian manual (+/-).
     * @return array<string, float>
     */
    public function computeForEmployee(Employee $employee, string $start, string $end, float $carryOver = 0.0): array
    {
        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $start)
            ->whereDate('work_date', '<=', $end)
            ->get();

        if ($employee->earning_type === 'piecework') {
            // Borongan/proyek: gaji = total item pekerjaan dalam periode, tanpa jam.
            $hours = 0.0;
            $gross = round((float) EmployeeWorkItem::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end)
                ->sum('amount'), 2);
        } else {
            $hours = round($attendances->sum(fn (Attendance $a) => $a->payableHours()), 2);
            $gross = round($attendances->sum(fn (Attendance $a) => $a->payableAmount((float) $employee->hourly_rate)), 2);
        }

        $bonus = (float) EmployeeBonus::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        // Kasbon: jumlah cicilan (repayment) yang tanggalnya di dalam periode ini.
        $loan = (float) EmployeeLoanRepayment::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        // Potongan ad-hoc (POTONGAN) yang tanggalnya di dalam periode ini.
        $deduction = (float) EmployeeDeduction::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        // Iuran arisan: kontribusi pending yang jatuh dalam periode ini (modul
        // Arisan yang menjadwalkan via openPeriod). Dipotong di payroll lalu
        // ditandai paid + di-link ke payroll saat dibayar (markPaid).
        $arisan = (float) ArisanContribution::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->whereDate('contribution_date', '>=', $start)
            ->whereDate('contribution_date', '<=', $end)
            ->sum('amount');

        $savings = (float) $employee->savings->where('active', true)->sum('amount');

        $thp = round($gross + $bonus + $carryOver - $loan - $deduction - $arisan - $savings, 2);

        return [
            'total_hours' => $hours,
            'gross_salary' => $gross,
            'total_bonus' => round($bonus, 2),
            'total_loan' => round($loan, 2),
            'total_deduction' => round($deduction, 2),
            'total_arisan' => round($arisan, 2),
            'total_savings' => round($savings, 2),
            'carry_over' => round($carryOver, 2),
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
            ->with(['savings'])
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

                // Pertahankan sisa gaji kemarin (input manual) saat hitung ulang.
                $carryOver = (float) ($existing->carry_over ?? 0);
                $components = $this->computeForEmployee($employee, $start, $end, $carryOver);

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
     * Tandai payroll dibayar lalu catat setoran tabungan periode itu.
     * (Cicilan kasbon sudah tercatat sebagai repayment, tidak diubah di sini.)
     */
    public function markPaid(Payroll $payroll): void
    {
        if ($payroll->status === 'paid') {
            return;
        }

        DB::transaction(function () use ($payroll) {
            $payroll->update(['status' => 'paid']);

            // Catat setoran tabungan ke buku tabungan (idempoten per payroll).
            $savings = (float) $payroll->total_savings;
            if ($savings > 0) {
                EmployeeSavingEntry::query()->firstOrCreate(
                    ['payroll_id' => $payroll->id, 'type' => 'deposit'],
                    [
                        'employee_id' => $payroll->employee_id,
                        'date' => $payroll->period_end,
                        'amount' => $savings,
                        'note' => 'Setoran dari payroll '.$payroll->period_start->format('d/m/Y').'–'.$payroll->period_end->format('d/m/Y'),
                    ],
                );
            }

            // Tandai iuran arisan periode ini sebagai terkumpul + link ke payroll.
            $contributions = ArisanContribution::query()
                ->where('employee_id', $payroll->employee_id)
                ->where('status', 'pending')
                ->whereDate('contribution_date', '>=', $payroll->period_start)
                ->whereDate('contribution_date', '<=', $payroll->period_end)
                ->get();

            $touchedPeriods = [];
            foreach ($contributions as $contribution) {
                $contribution->update(['status' => 'paid', 'payroll_id' => $payroll->id]);
                $touchedPeriods[$contribution->arisan_period_id] = true;
            }
            foreach (array_keys($touchedPeriods) as $periodId) {
                $period = ArisanPeriod::find($periodId);
                if ($period) {
                    $total = (float) $period->contributions()->where('status', 'paid')->sum('amount');
                    $period->update(['total_collected' => round($total, 2)]);
                }
            }
        });
    }
}
