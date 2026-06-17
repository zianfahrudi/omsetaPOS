<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2PayrollRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_recap_shows_monthly_totals(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $emps = collect(['Andi', 'Budi'])->map(fn ($name, $i) => Employee::create([
            'company_id' => $company->id, 'code' => 'E'.$i, 'name' => $name,
            'hourly_rate' => 0, 'is_active' => true,
        ]));

        Payroll::create([
            'company_id' => $company->id, 'employee_id' => $emps[0]->id,
            'period_start' => $start, 'period_end' => $end,
            'total_hours' => 100, 'gross_salary' => 3000000, 'total_bonus' => 200000,
            'total_loan' => 100000, 'total_arisan' => 0, 'total_savings' => 0,
            'take_home_pay' => 3100000, 'status' => 'draft',
        ]);
        Payroll::create([
            'company_id' => $company->id, 'employee_id' => $emps[1]->id,
            'period_start' => $start, 'period_end' => $end,
            'total_hours' => 80, 'gross_salary' => 2000000, 'total_bonus' => 0,
            'total_loan' => 500000, 'total_arisan' => 0, 'total_savings' => 0,
            'take_home_pay' => 1500000, 'status' => 'draft',
        ]);

        $month = now()->format('Y-m');

        $res = $this->actingAs($admin)->get(route('v2.payrolls.recap.salary', ['month' => $month]));
        $res->assertOk();
        $res->assertSee('Andi');
        $res->assertSee('Budi');
        $res->assertSee('Rp 5.000.000'); // total gaji kotor
        $res->assertSee('Rp 4.600.000'); // total THP

        $this->actingAs($admin)->get(route('v2.payrolls.recap.salary.print', ['month' => $month]))->assertOk();
    }

    public function test_loan_recap_lists_kasbon_for_month(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $emp = Employee::create([
            'company_id' => $company->id, 'code' => 'E9', 'name' => 'Citra',
            'hourly_rate' => 0, 'is_active' => true,
        ]);

        $thisMonth = now()->startOfMonth()->addDays(5)->toDateString();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(5)->toDateString();

        EmployeeLoan::create(['employee_id' => $emp->id, 'amount' => 250000, 'date' => $thisMonth, 'description' => 'Kasbon bulan ini', 'status' => 'pending']);
        EmployeeLoan::create(['employee_id' => $emp->id, 'amount' => 999000, 'date' => $lastMonth, 'description' => 'Kasbon bulan lalu', 'status' => 'deducted']);

        $month = now()->format('Y-m');

        $res = $this->actingAs($admin)->get(route('v2.payrolls.recap.loan', ['month' => $month]));
        $res->assertOk();
        $res->assertSee('Kasbon bulan ini');
        $res->assertDontSee('Kasbon bulan lalu'); // hanya bulan terpilih
        $res->assertSee('Rp 250.000');

        $this->actingAs($admin)->get(route('v2.payrolls.recap.loan.print', ['month' => $month]))->assertOk();
    }

    public function test_salary_recap_filter_by_employee(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $andi = Employee::create(['company_id' => $company->id, 'code' => 'A', 'name' => 'Andi', 'hourly_rate' => 0, 'is_active' => true]);
        $budi = Employee::create(['company_id' => $company->id, 'code' => 'B', 'name' => 'Budi', 'hourly_rate' => 0, 'is_active' => true]);

        foreach ([$andi, $budi] as $e) {
            Payroll::create([
                'company_id' => $company->id, 'employee_id' => $e->id,
                'period_start' => $start, 'period_end' => $end,
                'total_hours' => 10, 'gross_salary' => 1000000, 'total_bonus' => 0,
                'total_loan' => 0, 'total_arisan' => 0, 'total_savings' => 0,
                'take_home_pay' => 1000000, 'status' => 'draft',
            ]);
        }

        $month = now()->format('Y-m');

        $res = $this->actingAs($admin)->get(route('v2.payrolls.recap.salary', ['month' => $month, 'employee_id' => $andi->id]));
        $res->assertOk()->assertSee('Andi')->assertSee('1 karyawan');
    }

    public function test_loan_recap_filter_by_employee(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $andi = Employee::create(['company_id' => $company->id, 'code' => 'A', 'name' => 'Andi', 'hourly_rate' => 0, 'is_active' => true]);
        $budi = Employee::create(['company_id' => $company->id, 'code' => 'B', 'name' => 'Budi', 'hourly_rate' => 0, 'is_active' => true]);

        $date = now()->startOfMonth()->addDays(3)->toDateString();
        EmployeeLoan::create(['employee_id' => $andi->id, 'amount' => 100000, 'date' => $date, 'description' => 'Bon Andi', 'status' => 'pending']);
        EmployeeLoan::create(['employee_id' => $budi->id, 'amount' => 200000, 'date' => $date, 'description' => 'Bon Budi', 'status' => 'pending']);

        $month = now()->format('Y-m');

        $res = $this->actingAs($admin)->get(route('v2.payrolls.recap.loan', ['month' => $month, 'employee_id' => $andi->id]));
        $res->assertOk()->assertSee('Bon Andi')->assertDontSee('Bon Budi');
    }
}
