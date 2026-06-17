<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollGenerateTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_computes_thp_and_only_counts_loans_within_period(): void
    {
        $company = Company::create(['name' => 'Co', 'code' => 'C', 'currency' => 'IDR']);
        $emp = Employee::create([
            'company_id' => $company->id, 'code' => 'E1', 'name' => 'Abdurrahman',
            'hourly_rate' => 12142, 'is_active' => true,
        ]);

        $start = '2026-06-01';
        $end = '2026-06-30';

        // 2 hari absensi dengan paid_hours 4 jam → 8 jam total.
        Attendance::create(['employee_id' => $emp->id, 'work_date' => '2026-06-02', 'total_hours' => 4, 'paid_hours' => 4, 'status' => 'present']);
        Attendance::create(['employee_id' => $emp->id, 'work_date' => '2026-06-03', 'total_hours' => 4, 'paid_hours' => 4, 'status' => 'present']);

        EmployeeBonus::create(['employee_id' => $emp->id, 'date' => '2026-06-10', 'amount' => 50000, 'type' => 'target']);

        // Kasbon dengan cicilan DALAM periode → cicilan dipotong.
        $loanIn = EmployeeLoan::create(['employee_id' => $emp->id, 'amount' => 30000, 'outstanding' => 30000, 'date' => '2026-06-05', 'status' => 'pending']);
        EmployeeLoanRepayment::create(['employee_loan_id' => $loanIn->id, 'employee_id' => $emp->id, 'date' => '2026-06-05', 'amount' => 30000]);
        // Kasbon + cicilan LUAR periode (bulan lalu) → TIDAK dipotong di Juni.
        $loanOut = EmployeeLoan::create(['employee_id' => $emp->id, 'amount' => 999000, 'outstanding' => 999000, 'date' => '2026-05-20', 'status' => 'pending']);
        EmployeeLoanRepayment::create(['employee_loan_id' => $loanOut->id, 'employee_id' => $emp->id, 'date' => '2026-05-20', 'amount' => 999000]);

        $service = app(PayrollService::class);
        $c = $service->computeForEmployee($emp, $start, $end);

        // 8 jam × 12.142 = 97.136
        $this->assertSame(8.0, $c['total_hours']);
        $this->assertSame(97136.0, $c['gross_salary']);
        $this->assertSame(50000.0, $c['total_bonus']);
        $this->assertSame(30000.0, $c['total_loan']); // hanya cicilan Juni
        // THP = 97.136 + 50.000 - 30.000 = 117.136
        $this->assertSame(117136.0, $c['take_home_pay']);
    }

    public function test_generate_creates_drafts_and_mark_paid_deducts_period_loans(): void
    {
        $company = Company::create(['name' => 'Co2', 'code' => 'C2', 'currency' => 'IDR']);
        $emp = Employee::create([
            'company_id' => $company->id, 'code' => 'E1', 'name' => 'Budi',
            'hourly_rate' => 10000, 'is_active' => true,
        ]);
        Attendance::create(['employee_id' => $emp->id, 'work_date' => '2026-06-02', 'total_hours' => 5, 'paid_hours' => 5, 'status' => 'present']);
        $loan = EmployeeLoan::create(['employee_id' => $emp->id, 'amount' => 20000, 'outstanding' => 20000, 'date' => '2026-06-05', 'status' => 'pending']);
        EmployeeLoanRepayment::create(['employee_loan_id' => $loan->id, 'employee_id' => $emp->id, 'date' => '2026-06-05', 'amount' => 20000]);

        $service = app(PayrollService::class);
        $res = $service->generateForPeriod($company->id, '2026-06-01', '2026-06-30');
        $this->assertSame(1, $res['generated']);

        $payroll = Payroll::query()->where('employee_id', $emp->id)->firstOrFail();
        $this->assertSame('draft', $payroll->status);
        $this->assertSame('30000.00', (string) $payroll->take_home_pay); // 50.000 - 20.000

        // Re-generate tidak menggandakan.
        $service->generateForPeriod($company->id, '2026-06-01', '2026-06-30');
        $this->assertSame(1, Payroll::query()->where('employee_id', $emp->id)->count());

        // Mark paid → payroll tidak ditimpa lagi pada generate berikutnya.
        $payroll->update(['status' => 'approved']);
        $service->markPaid($payroll->fresh());

        $res2 = $service->generateForPeriod($company->id, '2026-06-01', '2026-06-30');
        $this->assertSame(1, $res2['skipped']);
        $this->assertSame('paid', $payroll->fresh()->status);
    }
}
