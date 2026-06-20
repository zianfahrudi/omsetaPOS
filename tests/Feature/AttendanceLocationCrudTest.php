<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLocationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_with_map(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(route('v2.attendance-locations.create'))
            ->assertOk()
            ->assertSee('id="map"', false)
            ->assertSee('map-search', false);
    }

    public function test_store_attendance_location(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('v2.attendance-locations.store'), [
            'name' => 'Kantor Pusat',
            'address' => 'Jl. Sudirman',
            'latitude' => -6.2000000,
            'longitude' => 106.8166600,
            'radius_meters' => 150,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_locations', [
            'name' => 'Kantor Pusat',
            'radius_meters' => 150,
        ]);
    }

    public function test_index_shows_map_with_points(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        AttendanceLocation::create([
            'company_id' => $company->id,
            'name' => 'Gudang A',
            'latitude' => -6.2,
            'longitude' => 106.8166600,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('v2.attendance-locations.index'))
            ->assertOk()
            ->assertSee('id="map"', false)
            ->assertSee('Gudang A');
    }
}
