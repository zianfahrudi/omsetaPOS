<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $attrs = []): Employee
    {
        $company = Company::query()->firstOrFail();

        $location = AttendanceLocation::create([
            'company_id' => $company->id,
            'name' => 'Kantor Pusat',
            'latitude' => -6.2000000,
            'longitude' => 106.8166600,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        return Employee::create(array_merge([
            'company_id' => $company->id,
            'code' => 'E1',
            'name' => 'Budi',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'attendance_location_id' => $location->id,
            'hourly_rate' => 10000,
            'is_active' => true,
        ], $attrs));
    }

    public function test_employee_can_login_and_receive_token(): void
    {
        $this->seed();
        $this->makeEmployee();

        $this->postJson(route('api.v1.employee.auth.login'), [
            'phone' => '081234567890',
            'password' => 'rahasia123',
        ])->assertOk()->assertJsonStructure(['token', 'employee' => ['id', 'name', 'location']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->seed();
        $this->makeEmployee();

        $this->postJson(route('api.v1.employee.auth.login'), [
            'phone' => '081234567890',
            'password' => 'salah',
        ])->assertStatus(422);
    }

    public function test_check_in_within_radius_succeeds(): void
    {
        $this->seed();
        $employee = $this->makeEmployee();
        Sanctum::actingAs($employee);

        $this->postJson(route('api.v1.employee.attendance.check-in'), [
            'latitude' => -6.2000500, // ~5 m dari titik
            'longitude' => 106.8166600,
            'accuracy' => 12.0,
            'is_mock' => false,
        ])->assertCreated()->assertJsonPath('attendance.status', 'present');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'source' => 'mobile',
        ]);
    }

    public function test_check_in_rejected_when_mock_gps(): void
    {
        $this->seed();
        $employee = $this->makeEmployee();
        Sanctum::actingAs($employee);

        $this->postJson(route('api.v1.employee.attendance.check-in'), [
            'latitude' => -6.2000500,
            'longitude' => 106.8166600,
            'accuracy' => 12.0,
            'is_mock' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('location');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_rejected_when_outside_radius(): void
    {
        $this->seed();
        $employee = $this->makeEmployee();
        Sanctum::actingAs($employee);

        $this->postJson(route('api.v1.employee.attendance.check-in'), [
            'latitude' => -6.2000000,
            'longitude' => 106.8300000, // ~1.5 km dari titik
            'accuracy' => 12.0,
            'is_mock' => false,
        ])->assertStatus(422)->assertJsonValidationErrors('location');
    }

    public function test_check_in_rejected_when_accuracy_too_low(): void
    {
        $this->seed();
        $employee = $this->makeEmployee();
        Sanctum::actingAs($employee);

        $this->postJson(route('api.v1.employee.attendance.check-in'), [
            'latitude' => -6.2000500,
            'longitude' => 106.8166600,
            'accuracy' => 500.0, // > 100 m ambang
            'is_mock' => false,
        ])->assertStatus(422)->assertJsonValidationErrors('location');
    }

    public function test_full_check_in_then_check_out_flow(): void
    {
        $this->seed();
        $employee = $this->makeEmployee();
        Sanctum::actingAs($employee);

        $payload = [
            'latitude' => -6.2000500,
            'longitude' => 106.8166600,
            'accuracy' => 10.0,
            'is_mock' => false,
        ];

        $this->postJson(route('api.v1.employee.attendance.check-in'), $payload)->assertCreated();

        // Tidak boleh check-in dua kali.
        $this->postJson(route('api.v1.employee.attendance.check-in'), $payload)->assertStatus(422);

        $this->postJson(route('api.v1.employee.attendance.check-out'), $payload)
            ->assertOk()->assertJsonPath('message', 'Check-out berhasil.');

        // Tidak boleh check-out dua kali.
        $this->postJson(route('api.v1.employee.attendance.check-out'), $payload)->assertStatus(422);
    }
}
