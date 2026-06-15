<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierVehicleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicles_endpoint_returns_last_service_info(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $store = $admin->accessibleStores()->firstOrFail();

        $customer = Customer::query()->create(['store_id' => $store->id, 'name' => 'Pak Servis']);
        $vehicle = CustomerVehicle::query()->create([
            'store_id' => $store->id, 'customer_id' => $customer->id, 'plate_number' => 'DD 9 XX', 'mileage' => 10000,
        ]);

        $product = Product::query()->where('store_id', $store->id)
            ->where('product_type', '!=', 'service')->where('stock', '>', 2)->firstOrFail();

        // Servis: penjualan terkait kendaraan dengan KM 25.000.
        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $admin->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000000,
            customerId: $customer->id,
            vehiclePlateNumber: 'DD 9 XX',
            vehicleMileage: 25000,
        );

        $response = $this->actingAs($admin)->getJson(route('cashier.vehicles', ['store_id' => $store->id, 'q' => 'DD 9']));
        $response->assertOk();

        $data = $response->json('vehicles');
        $this->assertNotEmpty($data);
        $row = collect($data)->firstWhere('id', $vehicle->id);
        $this->assertNotNull($row);
        $this->assertEquals(25000, $row['last_service_mileage']);
        $this->assertNotNull($row['last_service_at']);
        $this->assertArrayHasKey('last_service_summary', $row);
    }
}
