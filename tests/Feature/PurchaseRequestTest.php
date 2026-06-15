<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\PurchaseRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_converts_to_purchase_order(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $owner = User::create([
            'name' => 'Owner', 'email' => 'o@test.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $owner->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = app(PurchaseRequestService::class);

        $pr = $service->create(
            company: $company, contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000]],
        );

        $this->assertSame(50000.0, (float) $pr->grand_total);
        $this->assertSame('draft', $pr->status);

        $pr = $service->convertToOrder($pr);

        $this->assertSame('ordered', $pr->status);
        $this->assertNotNull($pr->purchase_order_id);
        $this->assertSame(1, PurchaseOrder::where('company_id', $company->id)->count());
    }
}
