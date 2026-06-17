<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2PayrollGenerateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_generates_payroll_for_month_via_ui(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $emp = Employee::create([
            'company_id' => $company->id, 'code' => 'E1', 'name' => 'Dewi',
            'hourly_rate' => 10000, 'is_active' => true,
        ]);
        Attendance::create([
            'employee_id' => $emp->id,
            'work_date' => now()->startOfMonth()->addDay()->toDateString(),
            'total_hours' => 6, 'paid_hours' => 6, 'status' => 'present',
        ]);

        $month = now()->format('Y-m');

        $this->actingAs($admin)->get(route('v2.payrolls.index', ['month' => $month]))
            ->assertOk()->assertSee('Generate Payroll');

        $this->actingAs($admin)->post(route('v2.payrolls.generate'), ['month' => $month])
            ->assertRedirect();

        $payroll = Payroll::query()->where('employee_id', $emp->id)->firstOrFail();
        $this->assertSame('60000.00', (string) $payroll->take_home_pay);

        // Regenerate tidak menggandakan.
        $this->actingAs($admin)->post(route('v2.payrolls.generate'), ['month' => $month]);
        $this->assertSame(1, Payroll::query()->where('employee_id', $emp->id)->count());

        // Bulk approve → pay.
        $this->actingAs($admin)->post(route('v2.payrolls.bulk.approve'), ['month' => $month])->assertRedirect();
        $this->assertSame('approved', $payroll->fresh()->status);

        $this->actingAs($admin)->post(route('v2.payrolls.bulk.pay'), ['month' => $month])->assertRedirect();
        $this->assertSame('paid', $payroll->fresh()->status);
    }
}
