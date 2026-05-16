<?php

namespace Tests\Feature;

use App\Filament\Pages\SalesReports;
use App\Filament\Resources\CustomerVehicles\Pages\ListCustomerVehicles;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Store;
use App\Models\StoreCharge;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_decrements_stock_and_writes_sale_log(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 30000,
        );

        $this->assertSame(8, $product->refresh()->stock);
        $this->assertSame('completed', $sale->status);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => -2,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sale.completed',
            'subject_id' => $sale->id,
        ]);
    }

    public function test_filament_user_create_page_has_store_selection_for_cashier_and_admin(): void
    {
        [$superuser] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $response = $this->actingAs($superuser)->get('/admin/users/create');

        $response->assertOk();
        $response->assertSee('Toko terdaftar');
        $response->assertSee('Cashier');
        $response->assertSee('Admin');
    }

    public function test_standalone_cashier_page_renders_checkout_shell(): void
    {
        [$cashier, $store] = $this->fixture();
        $otherStore = Store::create([
            'owner_id' => $cashier->id,
            'name' => 'Toko Kedua',
            'code' => 'TEST-02',
            'is_active' => true,
        ]);
        $unassignedStore = Store::create([
            'owner_id' => $cashier->id,
            'name' => 'Toko Tidak Terdaftar',
            'code' => 'TEST-03',
            'is_active' => true,
        ]);
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);
        $cashier->stores()->attach($otherStore->id, ['role' => 'cashier', 'is_default' => false]);

        $response = $this->actingAs($cashier)->get('/kasir');

        $response->assertOk();
        $response->assertSee('Kasir omsetaPOS');
        $response->assertSee('Refund');
        $response->assertSee('Riwayat Transaksi');
        $response->assertSee('Detail Transaksi');
        $response->assertSee('Print Nota');
        $response->assertSee('Cari Produk');
        $response->assertDontSee('<input class="input" id="scan"', false);
        $response->assertSee('Pesanan Saat Ini');
        $response->assertSee('Proses Pembayaran');
        $response->assertSee('Transaksi hutang piutang');
        $response->assertSee('Nomor Plat');
        $response->assertSee('Kilometer');
        $response->assertSee('Nominal Sudah Dibayar');
        $response->assertSee('id="paid-amount" type="text" inputmode="numeric"', false);
        $response->assertSee('id="refund-additional-payment" type="text" inputmode="numeric"', false);
        $response->assertSee($store->name);
        $response->assertSee($otherStore->name);
        $response->assertDontSee($unassignedStore->name);
        $response->assertSee(route('cashier.products'));
        $response->assertSee(route('cashier.transactions'));
        $response->assertSee(route('cashier.transactions.mark-paid', ['sale' => 0]));
        $response->assertSee(route('cashier.customers.store'));
        $response->assertSee(route('cashier.customers.check'));
        $response->assertSee(route('cashier.vehicles'));
        $response->assertSee(route('cashier.pricing'));
        $response->assertSee(route('cashier.checkout'));
        $response->assertSee(route('cashier.refunds.store'));
    }

    public function test_cashier_products_endpoint_returns_catalog_with_images(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);
        $product->update([
            'image_url' => '/product-images/kopi-susu.svg',
            'product_service_fee_type' => 'percentage',
            'product_service_fee' => 5,
            'product_tax_type' => 'percentage',
            'product_tax_value' => 10,
        ]);

        $response = $this->actingAs($cashier)->getJson(route('cashier.products', [
            'store_id' => $store->id,
            'q' => 'Produk',
        ]));

        $response->assertOk();
        $response->assertJsonPath('products.0.name', $product->name);
        $response->assertJsonPath('products.0.image_url', '/product-images/kopi-susu.svg');
        $response->assertJsonPath('products.0.product_service_fee_type', 'percentage');
        $response->assertJsonPath('products.0.product_service_fee_value', 5);
        $response->assertJsonPath('products.0.product_service_fee', 250);
        $response->assertJsonPath('products.0.product_tax_type', 'percentage');
        $response->assertJsonPath('products.0.product_tax_value', 10);
        $response->assertJsonPath('products.0.product_tax_amount', 500);

        $this->actingAs($cashier)->getJson(route('cashier.products', [
            'store_id' => $store->id,
            'product_id' => $product->id,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.product_service_fee_type', 'percentage');
    }

    public function test_cashier_cannot_access_unassigned_store_products(): void
    {
        [$cashier, $assignedStore] = $this->fixture();
        $unassignedStore = Store::create([
            'owner_id' => $cashier->id,
            'name' => 'Toko Lain',
            'code' => 'OTHER-01',
            'is_active' => true,
        ]);

        $cashier->stores()->attach($assignedStore->id, ['role' => 'cashier', 'is_default' => true]);

        $this->actingAs($cashier)->getJson(route('cashier.products', [
            'store_id' => $unassignedStore->id,
        ]))->assertForbidden();
    }

    public function test_cashier_transactions_endpoint_returns_only_own_history_and_searches(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $otherCashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);
        $otherCashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $ownSale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerName: 'Budi Riwayat',
        );

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $otherCashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerName: 'Customer Kasir Lain',
        );

        $response = $this->actingAs($cashier)->getJson(route('cashier.transactions', [
            'store_id' => $store->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'transactions')
            ->assertJsonPath('transactions.0.number', $ownSale->number)
            ->assertJsonPath('transactions.0.store_name', $store->name)
            ->assertJsonPath('transactions.0.cashier_name', $cashier->name)
            ->assertJsonPath('transactions.0.status', 'completed')
            ->assertJsonPath('transactions.0.payment_status', 'lunas')
            ->assertJsonPath('transactions.0.items.0.name', $product->name);
        $this->assertSame(1, $response->json('transactions.0.items.0.refundable_quantity'));
        $this->assertEquals(5000, (float) $response->json('transactions.0.subtotal'));
        $this->assertEquals(0, (float) $response->json('transactions.0.discount_total'));
        $this->assertEquals(0, (float) $response->json('transactions.0.tax_total'));
        $this->assertEquals(0, (float) $response->json('transactions.0.service_fee_total'));

        $this->actingAs($cashier)->getJson(route('cashier.transactions', [
            'store_id' => $store->id,
            'q' => 'Budi Riwayat',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'transactions')
            ->assertJsonPath('transactions.0.customer_name', 'Budi Riwayat');

        $this->actingAs($cashier)->getJson(route('cashier.transactions', [
            'store_id' => $store->id,
            'q' => $ownSale->number,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'transactions');

        $this->actingAs($cashier)->getJson(route('cashier.transactions', [
            'store_id' => $store->id,
            'q' => 'Produk Test',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'transactions');

        $this->actingAs($cashier)->getJson(route('cashier.transactions', [
            'store_id' => $store->id,
            'q' => 'Customer Kasir Lain',
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'transactions');
    }

    public function test_checkout_can_store_customer_phone(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'qris',
            paidAmount: 0,
            customerName: 'Budi',
            customerPhone: '081234567890',
        );

        $this->assertSame('Budi', $sale->customer_name);
        $this->assertSame('081234567890', $sale->customer_phone);
    }

    public function test_checkout_records_customer_vehicles_and_sale_snapshot(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Budi Kendaraan',
            'phone' => '081200000001',
        ]);

        $firstSale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerId: $customer->id,
            vehiclePlateNumber: 'dd 1234 xy',
            vehicleMileage: 12500,
        );

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerId: $customer->id,
            vehiclePlateNumber: 'DD 5678 AB',
            vehicleMileage: 8800,
        );

        $this->assertSame('DD 1234 XY', $firstSale->vehicle_plate_number);
        $this->assertSame(12500, $firstSale->vehicle_mileage);
        $this->assertSame(2, $customer->vehicles()->count());
        $this->assertDatabaseHas('customer_vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'DD 1234 XY',
            'mileage' => 12500,
        ]);
        $this->assertDatabaseHas('customer_vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'DD 5678 AB',
            'mileage' => 8800,
        ]);
    }

    public function test_service_product_can_checkout_without_stock_and_uses_product_fees(): void
    {
        [$cashier, $store] = $this->fixture();

        $service = Product::create([
            'store_id' => $store->id,
            'name' => 'Service Oli',
            'sku' => 'SRV-OLI',
            'cost_price' => 0,
            'sell_price' => 100000,
            'fee_amount' => 10000,
            'product_service_fee' => 15000,
            'product_type' => 'service',
            'stock' => 0,
            'minimum_stock' => 0,
            'unit' => 'jasa',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $service->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 300000,
        );

        $item = $sale->items()->firstOrFail();

        $this->assertSame(0, $service->refresh()->stock);
        $this->assertEquals(250000, (float) $sale->subtotal);
        $this->assertEquals(125000, (float) $item->unit_price);
        $this->assertEquals(10000, (float) $item->fee_amount);
        $this->assertEquals(15000, (float) $item->service_fee_amount);
        $this->assertSame('service', $item->product_type);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $service->id,
            'type' => 'sale',
        ]);
    }

    public function test_checkout_accepts_per_item_tax_and_service_fee_overrides(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [[
                'product_id' => $product->id,
                'quantity' => 2,
                'tax_amount' => 500,
                'service_fee_amount' => 1000,
            ]],
            paymentMethod: 'cash',
            paidAmount: 20000,
        );

        $item = $sale->items()->firstOrFail();

        $this->assertEquals(13000, (float) $sale->subtotal);
        $this->assertEquals(6500, (float) $item->unit_price);
        $this->assertEquals(500, (float) $item->tax_amount);
        $this->assertEquals(1000, (float) $item->service_fee_amount);
        $this->assertEquals(13000, (float) $item->line_total);
    }

    public function test_checkout_uses_product_percentage_tax_and_service_fee_defaults(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $product->update([
            'sell_price' => 100000,
            'fee_amount' => 10000,
            'product_service_fee_type' => 'percentage',
            'product_service_fee' => 5,
            'product_tax_type' => 'percentage',
            'product_tax_value' => 10,
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 200000,
        );

        $item = $sale->items()->firstOrFail();

        $this->assertEquals(126500, (float) $sale->subtotal);
        $this->assertEquals(126500, (float) $item->unit_price);
        $this->assertEquals(10000, (float) $item->fee_amount);
        $this->assertEquals(5500, (float) $item->service_fee_amount);
        $this->assertEquals(11000, (float) $item->tax_amount);
        $this->assertEquals(126500, (float) $item->line_total);
    }

    public function test_cashier_page_redirects_guest_to_login(): void
    {
        $this->get('/kasir')->assertRedirect('/admin/login');
    }

    public function test_cashier_checkout_endpoint_completes_order(): void
    {
        Storage::fake('public');

        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $response = $this->actingAs($cashier)->post(route('cashier.checkout'), [
            'store_id' => $store->id,
            'customer_name' => 'Sari',
            'customer_phone' => '081111111111',
            'vehicle_plate_number' => 'DD 9876 ZZ',
            'vehicle_mileage' => 21000,
            'payment_method' => 'qris',
            'paid_amount' => 5000,
            'payment_proof' => UploadedFile::fake()->image('qris-proof.jpg'),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('sale.customer_name', 'Sari');
        $response->assertJsonPath('sale.customer_phone', '081111111111');
        $response->assertJsonPath('sale.vehicle_plate_number', 'DD 9876 ZZ');
        $response->assertJsonPath('sale.vehicle_mileage', 21000);
        $response->assertJsonPath('sale.store_name', $store->name);
        $response->assertJsonPath('sale.cashier_name', $cashier->name);
        $response->assertJsonPath('sale.items.0.name', $product->name);
        $response->assertJsonPath('sale.items.0.quantity', 1);
        $this->assertNotNull($response->json('sale.payment_proof'));
        Storage::disk('public')->assertExists($response->json('sale.payment_proof'));
        $this->assertSame(9, $product->refresh()->stock);

        $customer = Customer::where('phone', '081111111111')->firstOrFail();

        $this->assertSame($store->id, $customer->store_id);
        $this->assertSame('Sari', $customer->name);
        $this->assertSame(1, $customer->visit_count);
        $this->assertEquals(5000, (float) $customer->total_spent);
        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'customer_name' => 'Sari',
            'customer_phone' => '081111111111',
            'vehicle_plate_number' => 'DD 9876 ZZ',
            'vehicle_mileage' => 21000,
        ]);
        $this->assertDatabaseHas('customer_vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'DD 9876 ZZ',
            'mileage' => 21000,
        ]);
    }

    public function test_manual_duplicate_customer_checkout_is_rejected(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerName: 'Budi',
            customerPhone: '081222222222',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pelanggan sudah terdaftar');

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 20000,
            customerName: 'Budi Update',
            customerPhone: '081222222222',
        );
    }

    public function test_selected_existing_customer_checkout_updates_crm_record(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Budi',
            'phone' => '081222222222',
            'visit_count' => 1,
            'total_spent' => 5000,
        ]);

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 20000,
            customerId: $customer->id,
        );

        $customer->refresh();

        $this->assertSame('Budi', $customer->name);
        $this->assertSame(2, $customer->visit_count);
        $this->assertEquals(15000, (float) $customer->total_spent);
        $this->assertNotNull($customer->last_purchase_at);
    }

    public function test_cashier_customer_duplicate_check_endpoint_detects_phone_and_name_only_without_phone(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'Budi',
            'phone' => '081333333333',
        ]);

        $this->actingAs($cashier)->getJson(route('cashier.customers.check', [
            'store_id' => $store->id,
            'name' => 'budi',
        ]))->assertOk()->assertJsonPath('exists', true);

        $this->actingAs($cashier)->getJson(route('cashier.customers.check', [
            'store_id' => $store->id,
            'name' => 'budi',
            'phone' => '081999999999',
        ]))->assertOk()->assertJsonPath('exists', false);

        $this->actingAs($cashier)->getJson(route('cashier.customers.check', [
            'store_id' => $store->id,
            'phone' => '081333333333',
        ]))->assertOk()->assertJsonPath('exists', true);
    }

    public function test_cashier_vehicle_search_endpoint_returns_customer_and_mileage(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Pelanggan Mobil',
            'phone' => '081515151515',
        ]);

        CustomerVehicle::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'plate_number' => 'DD 1515 PM',
            'mileage' => 15150,
        ]);

        $response = $this->actingAs($cashier)->getJson(route('cashier.vehicles', [
            'store_id' => $store->id,
            'q' => 'Pelanggan Mobil',
        ]));

        $response->assertOk();
        $response->assertJsonPath('vehicles.0.plate_number', 'DD 1515 PM');
        $response->assertJsonPath('vehicles.0.mileage', 15150);
        $response->assertJsonPath('vehicles.0.customer.name', 'Pelanggan Mobil');
        $response->assertJsonPath('vehicles.0.customer.phone', '081515151515');
    }

    public function test_cashier_can_create_manual_customer(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $response = $this->actingAs($cashier)->postJson(route('cashier.customers.store'), [
            'store_id' => $store->id,
            'name' => 'Manual Customer',
            'phone' => '081777777777',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('customer.name', 'Manual Customer');
        $response->assertJsonPath('customer.phone', '081777777777');
        $this->assertDatabaseHas('customers', [
            'store_id' => $store->id,
            'name' => 'Manual Customer',
            'phone' => '081777777777',
        ]);
    }

    public function test_cashier_manual_customer_create_rejects_duplicate(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'Customer Lama',
            'phone' => '081888888888',
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cashier.customers.store'), [
            'store_id' => $store->id,
            'name' => 'Customer Baru',
            'phone' => '081888888888',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Pelanggan sudah terdaftar. Pilih dari database pelanggan.');
    }

    public function test_cashier_manual_customer_create_allows_same_name_with_different_phone(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'Nama Sama',
            'phone' => '081888888888',
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cashier.customers.store'), [
            'store_id' => $store->id,
            'name' => 'Nama Sama',
            'phone' => '081999999999',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('customer.name', 'Nama Sama');
        $response->assertJsonPath('customer.phone', '081999999999');
    }

    public function test_checkout_applies_store_tax_service_fee_and_fixed_discount(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        StoreCharge::create([
            'store_id' => $store->id,
            'tax_percentage' => 10,
            'service_fee_percentage' => 5,
            'is_tax_active' => true,
            'is_service_fee_active' => true,
        ]);

        $discount = Discount::create([
            'store_id' => $store->id,
            'name' => 'Potongan 1000',
            'code' => 'POTONG1000',
            'type' => 'fixed',
            'value' => 1000,
            'minimum_spend' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 11000,
            discountCode: 'POTONG1000',
        );

        $this->assertEquals(10000, (float) $sale->subtotal);
        $this->assertEquals(1000, (float) $sale->discount_total);
        $this->assertEquals(450, (float) $sale->service_fee_total);
        $this->assertEquals(900, (float) $sale->tax_total);
        $this->assertEquals(10350, (float) $sale->grand_total);
        $this->assertSame('POTONG1000', $sale->discount_code);
        $this->assertSame(1, $discount->refresh()->used_count);
    }

    public function test_debt_checkout_records_sale_and_customer_debt(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Pelanggan Hutang',
            'phone' => '081444444444',
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 5000,
            customerId: $customer->id,
            isDebt: true,
        );

        $customer->refresh();

        $this->assertTrue($sale->is_debt);
        $this->assertEquals(5000, (float) $sale->debt_amount);
        $this->assertEquals(5000, (float) $sale->paid_amount);
        $this->assertEquals(5000, (float) $customer->outstanding_debt);
        $this->assertEquals(5000, (float) $customer->debt_total);
        $this->assertNotNull($customer->last_debt_at);
    }

    public function test_debt_checkout_requires_customer(): void
    {
        [$cashier, $store, $product] = $this->fixture();

        $this->actingAs($cashier);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaksi hutang wajib');

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 1000,
            isDebt: true,
        );
    }

    public function test_cashier_pricing_endpoint_previews_percentage_discount(): void
    {
        [$cashier, $store] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        StoreCharge::create([
            'store_id' => $store->id,
            'tax_percentage' => 10,
            'service_fee_percentage' => 5,
            'is_tax_active' => true,
            'is_service_fee_active' => true,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'name' => 'Hemat 10%',
            'code' => 'HEMAT10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_spend' => 10000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->getJson(route('cashier.pricing', [
            'store_id' => $store->id,
            'subtotal' => 20000,
            'discount_code' => 'HEMAT10',
        ]));

        $response->assertOk();
        $response->assertJsonPath('pricing.discount_code', 'HEMAT10');
        $response->assertJsonPath('pricing.discount_total', 2000);
        $response->assertJsonPath('pricing.service_fee_total', 900);
        $response->assertJsonPath('pricing.tax_total', 1800);
        $response->assertJsonPath('pricing.grand_total', 20700);
    }

    public function test_cashier_checkout_endpoint_accepts_debt_transaction(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Endpoint Hutang',
            'phone' => '081555555555',
        ]);

        $response = $this->actingAs($cashier)->post(route('cashier.checkout'), [
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'paid_amount' => 1000,
            'is_debt' => true,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('sale.is_debt', true);
        $response->assertJsonPath('sale.debt_amount', 4000);
        $this->assertEquals(4000, (float) $customer->refresh()->outstanding_debt);
    }

    public function test_cashier_can_mark_debt_transaction_paid(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Piutang Lunas',
            'phone' => '081313131313',
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 1000,
            customerId: $customer->id,
            isDebt: true,
        );

        $this->assertEquals(4000, (float) $sale->debt_amount);
        $this->assertEquals(4000, (float) $customer->refresh()->outstanding_debt);

        $response = $this->postJson(route('cashier.transactions.mark-paid', ['sale' => $sale->id]));

        $response->assertOk();
        $response->assertJsonPath('sale.payment_status', 'lunas');
        $response->assertJsonPath('sale.payment_status_label', 'Lunas');
        $response->assertJsonPath('sale.debt_amount', 0);
        $response->assertJsonPath('sale.paid_amount', 5000);
        $this->assertEquals(0, (float) $customer->refresh()->outstanding_debt);

        $report = app(SalesReports::class);
        $report->mount();
        $this->assertEquals(0, (float) $report->totals()['debt']);
        $this->assertEquals(5000, (float) $report->totals()['revenue']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sale.debt_paid',
            'subject_id' => $sale->id,
        ]);
    }

    public function test_customer_vehicle_cms_lists_latest_service_and_searches_customer_name(): void
    {
        [$superuser, $store, $product] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Andi Service',
            'phone' => '081616161616',
        ]);

        $vehicle = CustomerVehicle::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'plate_number' => 'DD 1212 AS',
            'mileage' => 12000,
        ]);
        $otherCustomer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Bukan Hasil',
            'phone' => '081616161617',
        ]);
        CustomerVehicle::create([
            'store_id' => $store->id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'DD 0000 XX',
            'mileage' => 5000,
        ]);

        $this->actingAs($superuser);

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $superuser->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerId: $customer->id,
            vehiclePlateNumber: $vehicle->plate_number,
            vehicleMileage: 13500,
        );

        $response = $this->get('/admin/customer-vehicles');

        $response->assertOk();
        $response->assertSee('Kendaraan');
        $response->assertSee('Andi Service');
        $response->assertSee('DD 1212 AS');
        $response->assertSee('13,500');
        $response->assertSee('Produk Test');

        Livewire::test(ListCustomerVehicles::class)
            ->set('tableSearch', 'Andi')
            ->assertSee('Andi Service')
            ->assertSee('DD 1212 AS')
            ->assertDontSee('DD 0000 XX');
    }

    public function test_sales_cms_edit_page_can_manage_payment_status(): void
    {
        [$superuser, $store, $product] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'CMS Hutang',
            'phone' => '081414141414',
        ]);

        $this->actingAs($superuser);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $superuser->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 1000,
            customerId: $customer->id,
            isDebt: true,
        );

        $response = $this->get("/admin/sales/{$sale->id}/edit");

        $response->assertOk();
        $response->assertSee('Status pembayaran');
        $response->assertSee('Belum lunas');
        $response->assertSee('Nominal hutang');
    }

    public function test_cashier_refund_endpoint_processes_full_refund_with_evidence_photos(): void
    {
        Storage::fake('public');

        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
        );

        $this->assertSame(9, $product->refresh()->stock);

        $response = $this->post(route('cashier.refunds.store'), [
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'type' => 'full',
            'reason' => 'Barang rusak',
            'evidence_photos' => [
                UploadedFile::fake()->image('refund-1.jpg'),
                UploadedFile::fake()->image('refund-2.jpg'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('sale_status', 'refunded');
        $response->assertJsonPath('refund.receipt_type', 'refund');
        $response->assertJsonPath('refund.type', 'full');
        $response->assertJsonPath('refund.refund_amount', 5000);
        $response->assertJsonPath('refund.sale_number', $sale->number);
        $response->assertJsonPath('refund.items.0.direction', 'returned');

        $refund = Refund::firstOrFail();
        $this->assertCount(2, $refund->evidence_photos);
        foreach ($refund->evidence_photos as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->assertSame('refunded', $sale->refresh()->status);
        $this->assertSame(10, $product->refresh()->stock);
    }

    public function test_cashier_exchange_refund_response_includes_additional_change_for_receipt(): void
    {
        Storage::fake('public');

        [$cashier, $store, $returnedProduct] = $this->fixture();
        $replacementProduct = Product::create([
            'store_id' => $store->id,
            'name' => 'Produk Pengganti Mahal',
            'sku' => 'MAHAL',
            'barcode' => '999003',
            'cost_price' => 2000,
            'sell_price' => 7000,
            'stock' => 5,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $returnedProduct->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
        );

        $response = $this->post(route('cashier.refunds.store'), [
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'type' => 'exchange',
            'additional_payment_amount' => 10000,
            'evidence_photos' => [
                UploadedFile::fake()->image('refund-exchange.jpg'),
            ],
            'returned_items' => [
                ['sale_item_id' => $sale->items()->firstOrFail()->id, 'quantity' => 1],
            ],
            'replacement_items' => [
                ['product_id' => $replacementProduct->id, 'quantity' => 1],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('refund.type', 'exchange');
        $response->assertJsonPath('refund.refund_amount', 0);
        $response->assertJsonPath('refund.additional_payment_amount', 2000);
        $response->assertJsonPath('refund.additional_paid_amount', 10000);
        $response->assertJsonPath('refund.change_amount', 8000);
        $response->assertJsonPath('refund.items.0.direction', 'returned');
        $response->assertJsonPath('refund.items.1.direction', 'replacement');
    }

    public function test_unpaid_debt_transaction_cannot_be_refunded(): void
    {
        Storage::fake('public');

        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Refund Belum Lunas',
            'phone' => '081515151515',
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 1000,
            customerId: $customer->id,
            isDebt: true,
        );

        $response = $this->post(route('cashier.refunds.store'), [
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'type' => 'full',
            'evidence_photos' => [
                UploadedFile::fake()->image('refund-unpaid.jpg'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Transaksi belum lunas tidak bisa direfund.');
    }

    public function test_sales_report_reduces_revenue_after_refund_and_stock_returns(): void
    {
        [$superuser, $store, $product] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $this->actingAs($superuser);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $superuser->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'cash',
            paidAmount: 10000,
        );

        $this->assertSame(9, $product->refresh()->stock);

        app(RefundService::class)->refund(
            saleId: $sale->id,
            handledById: $superuser->id,
            type: 'full',
            returnedItems: [['sale_item_id' => $sale->items()->firstOrFail()->id, 'quantity' => 1]],
        );

        $this->assertSame(10, $product->refresh()->stock);

        $page = app(SalesReports::class);
        $page->mount();

        $this->assertEquals(0, (float) $page->totals()['revenue']);
    }

    public function test_customers_cms_page_renders(): void
    {
        [$superuser, $store] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        Customer::create([
            'store_id' => $store->id,
            'name' => 'CRM Customer',
            'phone' => '089999999999',
        ]);

        $response = $this->actingAs($superuser)->get('/admin/customers');

        $response->assertOk();
        $response->assertSee('CRM Customer');
        $response->assertSee('089999999999');
        $response->assertSee('Hutang');
    }

    public function test_charge_and_discount_cms_pages_render(): void
    {
        [$superuser, $store] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        StoreCharge::create([
            'store_id' => $store->id,
            'tax_percentage' => 11,
            'service_fee_percentage' => 5,
            'is_tax_active' => true,
            'is_service_fee_active' => true,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'name' => 'Diskon CMS',
            'code' => 'CMS10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_spend' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($superuser)->get('/admin/store-charges')
            ->assertOk()
            ->assertSee('Toko Test')
            ->assertSee('11');

        $this->actingAs($superuser)->get('/admin/discounts')
            ->assertOk()
            ->assertSee('Diskon CMS')
            ->assertSee('CMS10');
    }

    public function test_sales_report_shows_debt_total(): void
    {
        [$superuser, $store, $product] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'Report Hutang',
            'phone' => '081666666666',
        ]);

        $this->actingAs($superuser);

        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $superuser->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 3000,
            customerId: $customer->id,
            isDebt: true,
        );

        $response = $this->get('/admin/sales-reports');

        $response->assertOk();
        $response->assertSee('Hutang');
        $response->assertSee('Rp 7.000');
    }

    public function test_sales_view_shows_qris_payment_proof(): void
    {
        [$superuser, $store, $product] = $this->fixture();
        $superuser->update(['role' => 'superuser']);

        $this->actingAs($superuser);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $superuser->id,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'qris',
            paidAmount: 0,
            paymentProof: 'payment-proofs/qris-proof.jpg',
        );

        $response = $this->get("/admin/sales/{$sale->id}");

        $response->assertOk();
        $response->assertSee('Bukti transfer / QRIS');
        $response->assertSee('payment-proofs/qris-proof.jpg');
    }

    public function test_refund_with_replacement_restores_and_deducts_stock(): void
    {
        [$cashier, $store, $returnedProduct] = $this->fixture();
        $replacementProduct = Product::create([
            'store_id' => $store->id,
            'name' => 'Produk Pengganti',
            'sku' => 'PENGGANTI',
            'barcode' => '999002',
            'cost_price' => 1000,
            'sell_price' => 7000,
            'stock' => 5,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $returnedProduct->id, 'quantity' => 1]],
            paymentMethod: 'qris',
            paidAmount: 0,
        );

        $saleItem = $sale->items()->firstOrFail();
        $refund = app(RefundService::class)->refund(
            saleId: $sale->id,
            handledById: $cashier->id,
            type: 'exchange',
            returnedItems: [['sale_item_id' => $saleItem->id, 'quantity' => 1]],
            replacementItems: [['product_id' => $replacementProduct->id, 'quantity' => 1]],
            additionalPaymentAmount: 2000,
        );

        $this->assertSame(10, $returnedProduct->refresh()->stock);
        $this->assertSame(4, $replacementProduct->refresh()->stock);
        $this->assertSame('refunded', $sale->refresh()->status);
        $this->assertEquals(2000, (float) $refund->additional_payment_amount);
    }

    /**
     * @return array{0: User, 1: Store, 2: Product}
     */
    private function fixture(): array
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $store = Store::create([
            'owner_id' => $cashier->id,
            'name' => 'Toko Test',
            'code' => 'TEST-01',
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Produk Test',
            'sku' => 'TEST',
            'barcode' => '999001',
            'cost_price' => 5000,
            'sell_price' => 5000,
            'stock' => 10,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        return [$cashier, $store, $product];
    }
}
