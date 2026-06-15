<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_converts_to_posted_purchase(): void
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
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = app(PurchaseOrderService::class);

        $order = $service->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 6000]],
        );

        $this->assertSame(60000.0, (float) $order->grand_total);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(0.0, app(LedgerService::class)->balance($company->account('accounts_payable')));

        $order = $service->convertToPurchase($order);

        $this->assertSame('received', $order->status);
        $this->assertNotNull($order->purchase_id);
        $this->assertSame(10, $product->refresh()->stock);
        $this->assertSame(60000.0, app(LedgerService::class)->balance($company->account('accounts_payable')));
    }
}
