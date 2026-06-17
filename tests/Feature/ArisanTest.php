<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Services\ArisanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ArisanTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployees(Company $company, int $n): array
    {
        $list = [];
        for ($i = 1; $i <= $n; $i++) {
            $list[] = Employee::create([
                'company_id' => $company->id,
                'code' => 'EMP-'.$i,
                'name' => 'Karyawan '.$i,
                'hourly_rate' => 0,
                'is_active' => true,
            ]);
        }

        return $list;
    }

    public function test_full_arisan_flow_group_member_period_collect_draw(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        $employees = $this->makeEmployees($company, 3);

        $service = app(ArisanService::class);

        $group = $service->createGroup([
            'name' => 'Arisan Bulanan',
            'contribution_amount' => 100000,
            'draw_method' => 'queue',
            'status' => 'draft',
        ], $company->id);

        foreach ($employees as $e) {
            $service->addMember($group, $e->id);
        }

        $group->refresh();
        $this->assertSame(3, $group->total_members);
        $this->assertSame('draft', $group->status);

        // Buka periode → 3 iuran pending.
        $period = $service->openPeriod($group);
        $this->assertSame(1, $period->period_no);
        $this->assertCount(3, $period->contributions);
        $this->assertSame('active', $group->fresh()->status);

        // Belum terkumpul → tidak bisa undi.
        try {
            $service->drawWinner($period, 'queue');
            $this->fail('Seharusnya gagal: iuran belum terkumpul');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('terkumpul', $e->getMessage());
        }

        // Tandai terkumpul.
        $period = $service->collectPeriod($period);
        $this->assertSame('300000.00', (string) $period->total_collected);

        // Undi queue → pemenang = sequence 1 (Karyawan 1).
        $payout = $service->drawWinner($period, 'queue');
        $this->assertSame('300000.00', (string) $payout->amount);
        $this->assertSame($employees[0]->id, $payout->employee_id);

        $period->refresh();
        $this->assertSame('completed', $period->status);
        $this->assertSame($employees[0]->id, $period->winner_employee_id);

        // Pemenang tidak bisa menang lagi (R1): periode 2, kandidat = 2 sisa.
        $period2 = $service->openPeriod($group);
        $service->collectPeriod($period2);
        $payout2 = $service->drawWinner($period2, 'queue');
        $this->assertNotSame($employees[0]->id, $payout2->employee_id);
        $this->assertSame($employees[1]->id, $payout2->employee_id);
    }

    public function test_withdrawn_member_excluded_from_draw(): void
    {
        $company = Company::create(['name' => 'Co2', 'code' => 'C2', 'currency' => 'IDR']);
        $employees = $this->makeEmployees($company, 2);
        $service = app(ArisanService::class);

        $group = $service->createGroup([
            'name' => 'Arisan B', 'contribution_amount' => 50000, 'draw_method' => 'queue',
        ], $company->id);

        $m1 = $service->addMember($group, $employees[0]->id);
        $service->addMember($group, $employees[1]->id);

        // Keluarkan anggota 1 sebelum buka periode.
        $m1->update(['status' => 'withdrawn']);

        $period = $service->openPeriod($group);
        // Hanya 1 iuran (anggota aktif).
        $this->assertCount(1, $period->contributions);

        $service->collectPeriod($period);
        $payout = $service->drawWinner($period, 'queue');
        $this->assertSame($employees[1]->id, $payout->employee_id);
    }
}
