<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesQuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_converts_to_sales_order(): void
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
        $customer = Contact::create(['company_id' => $company->id, 'name' => 'Pelanggan', 'type' => 'customer', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 50,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = app(SalesQuotationService::class);

        $quotation = $service->create(
            company: $company, contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 10000]],
        );

        $this->assertSame(40000.0, (float) $quotation->grand_total);
        $this->assertSame('draft', $quotation->status);

        $quotation = $service->convertToOrder($quotation);

        $this->assertSame('ordered', $quotation->status);
        $this->assertNotNull($quotation->sales_order_id);
        $this->assertSame(1, SalesOrder::where('company_id', $company->id)->count());
    }
}
